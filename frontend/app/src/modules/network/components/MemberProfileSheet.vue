<script setup lang="ts">
// Fiche d'une médiatrice (maquette Profil médiatrice) : en-tête, repères, onglets Profil,
// Événements et Ressources quand elle en a, Messages quand la personne connectée a déjà échangé
// avec elle (un onglet vide n'est pas affiché).
// Partagée par /reseau/:slug et « Mon profil » : les actions de l'en-tête arrivent par le
// slot « actions ». L'onglet ouvert figure dans l'URL (?onglet=messages).
// Événements : les trois prochains où elle est inscrite (carte de la colonne et onglet).
// Ressources : ses contributions (autrice, contributrice, citée), les deux plus récentes dans la
// colonne, toutes dans l'onglet.
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import ConversationThread from '@modules/messages/components/ConversationThread.vue';
import ResourceRow from '@modules/resources/components/ResourceRow.vue';
import { publishedLabel } from '@modules/resources/services/resources';
import type { MemberLink, MemberProfile, MemberResource } from '../services/members';
import MemberEventItem from './MemberEventItem.vue';
import ProfileHeader from './ProfileHeader.vue';
import ProfileMarks from './ProfileMarks.vue';

const props = defineProps<{ profile: MemberProfile }>();

const route = useRoute();
const router = useRouter();

type Tab = 'profil' | 'evenements' | 'ressources' | 'messages';
const TABS: Tab[] = ['profil', 'evenements', 'ressources', 'messages'];

const conversation = computed(() => props.profile.conversation);
/** Non-lus de l'onglet Messages, remis à zéro dès que la conversation est lue. */
const unread = ref(0);
watch(conversation, (value) => (unread.value = value?.unread_count ?? 0), { immediate: true });

const events = computed(() => props.profile.upcoming_events);
const resources = computed(() => props.profile.resources);

/** Onglets affichés : Profil toujours, les autres seulement s'ils ont du contenu. */
function isShown(name: Tab): boolean {
  switch (name) {
    case 'evenements':
      return events.value.length > 0;
    case 'ressources':
      return resources.value.length > 0;
    case 'messages':
      return conversation.value !== null;
    default:
      return true;
  }
}

/** Onglet de l'URL, ou Profil s'il n'existe pas ou n'est pas affiché pour cette médiatrice. */
function tabFromUrl(): Tab {
  const wanted = TABS.find((name) => name === route.query.onglet) ?? 'profil';
  return isShown(wanted) ? wanted : 'profil';
}

const tab = ref<Tab>(tabFromUrl());
watch(
  [() => route.query.onglet, conversation, events, resources],
  () => (tab.value = tabFromUrl())
);
watch(tab, (value) => {
  const onglet = value === 'profil' ? undefined : value;
  if (route.query.onglet !== onglet) void router.replace({ query: { ...route.query, onglet } });
});

const messagesLabel = computed(() =>
  unread.value > 0
    ? `Messages, ${unread.value} ${unread.value > 1 ? 'non lus' : 'non lu'}`
    : 'Messages'
);

const eventsCount = computed(() =>
  events.value.length > 1
    ? `${events.value.length} rendez-vous où ${props.profile.name} sera présente`
    : `1 rendez-vous où ${props.profile.name} sera présente`
);

const RECENT_RESOURCES = 2;

/** « Rapport · Avril 2026 · Contributrice ». */
function resourceEyebrow(resource: MemberResource): string {
  return [resource.type.label, publishedLabel(resource.published_on), resource.role.label].join(
    ' · '
  );
}

const isEmpty = computed(
  () =>
    !props.profile.bio &&
    !props.profile.expertises.length &&
    !props.profile.zones.length &&
    !props.profile.links.length
);

const LINK_ICONS: Record<MemberLink['type'], string> = {
  linkedin: 'brand-linkedin',
  website: 'world',
  organization: 'building',
  other: 'link',
};

/** Adresse sans « https:// » ni barre finale, pour l'affichage. */
function displayUrl(url: string): string {
  return url.replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '');
}
</script>

<template>
  <div class="profile-sheet">
    <div class="container">
      <ProfileHeader :profile="profile">
        <template v-if="$slots.actions" #actions><slot name="actions" /></template>
      </ProfileHeader>
      <ProfileMarks :profile="profile" />
    </div>

    <div class="profile-sheet__tabs">
      <div class="container">
        <q-tabs
          v-model="tab"
          no-caps
          align="left"
          active-color="primary"
          indicator-color="primary"
          narrow-indicator
          class="profile-tabs"
          aria-label="Sections du profil"
        >
          <q-tab name="profil" label="Profil" />
          <q-tab v-if="events.length" name="evenements">
            <span class="q-tab__label">
              Événements<span class="profile-tabs__count">{{ events.length }}</span>
            </span>
          </q-tab>
          <q-tab v-if="resources.length" name="ressources">
            <span class="q-tab__label">
              Ressources<span class="profile-tabs__count">{{ resources.length }}</span>
            </span>
          </q-tab>
          <q-tab v-if="conversation" name="messages" :aria-label="messagesLabel">
            <span class="q-tab__label">Messages</span>
            <q-badge
              v-if="unread > 0"
              rounded
              :label="unread"
              class="profile-tabs__badge"
              aria-hidden="true"
            />
          </q-tab>
        </q-tabs>
      </div>
    </div>

    <q-tab-panels v-model="tab" class="profile-sheet__panels container">
      <q-tab-panel name="profil" class="profile-body">
        <q-card flat tag="article" class="rimef-card profile-main">
          <p v-if="isEmpty" class="profile-main__empty">
            {{ profile.first_name }} n’a pas encore complété son profil.
          </p>

          <section v-if="profile.bio" class="profile-section" aria-labelledby="profile-about">
            <h2 id="profile-about">À propos</h2>
            <p class="profile-section__bio">{{ profile.bio }}</p>
          </section>

          <section
            v-if="profile.expertises.length"
            class="profile-section"
            aria-labelledby="profile-expertises"
          >
            <h2 id="profile-expertises">Domaines d’expertise</h2>
            <ul class="profile-chips">
              <li v-for="expertise in profile.expertises" :key="expertise">
                <q-chip :ripple="false" class="profile-chip">{{ expertise }}</q-chip>
              </li>
            </ul>
          </section>

          <section
            v-if="profile.zones.length"
            class="profile-section"
            aria-labelledby="profile-zones"
          >
            <h2 id="profile-zones">Zones d’intervention</h2>
            <ul class="profile-chips">
              <li v-for="zone in profile.zones" :key="zone.code">
                <q-chip :ripple="false" class="profile-chip">{{ zone.name }}</q-chip>
              </li>
            </ul>
          </section>

          <section
            v-if="profile.links.length"
            class="profile-section"
            aria-labelledby="profile-links"
          >
            <h2 id="profile-links">Liens</h2>
            <ul class="profile-links">
              <li v-for="link in profile.links" :key="link.type">
                <q-icon :name="LINK_ICONS[link.type]" size="18px" aria-hidden="true" />
                <span class="profile-links__label">{{ link.label }}</span>
                <a :href="link.url" target="_blank" rel="noopener noreferrer nofollow">
                  {{ displayUrl(link.url) }}
                  <span class="sr-only">(nouvel onglet)</span>
                </a>
              </li>
            </ul>
          </section>
        </q-card>

        <aside class="profile-side" :aria-label="`En lien avec ${profile.name}`">
          <q-card flat tag="section" class="rimef-card side-card" aria-labelledby="profile-next">
            <div class="card-head">
              <h2 id="profile-next" class="card-title">Prochains événements</h2>
              <q-btn
                v-if="events.length"
                flat
                no-caps
                label="Voir tous"
                class="link-more"
                @click="tab = 'evenements'"
              />
            </div>
            <ul v-if="events.length" class="profile-events">
              <MemberEventItem v-for="event in events" :key="event.id" :event="event" />
            </ul>
            <p v-else class="side-card__empty">Aucun événement à venir pour le moment.</p>
          </q-card>
          <q-card
            flat
            tag="section"
            class="rimef-card side-card"
            aria-labelledby="profile-contributions"
          >
            <div class="card-head">
              <h2 id="profile-contributions" class="card-title">Contributions récentes</h2>
              <q-btn
                v-if="resources.length"
                flat
                no-caps
                label="Voir toutes"
                class="link-more"
                @click="tab = 'ressources'"
              />
            </div>
            <ul v-if="resources.length" class="profile-resources">
              <ResourceRow
                v-for="resource in resources.slice(0, RECENT_RESOURCES)"
                :key="resource.id"
                :slug="resource.slug"
                :title="resource.title"
                :type="resource.type"
                :published-on="resource.published_on"
                :source="resource.source"
                :facts="`${resource.type.label} · ${publishedLabel(resource.published_on)}`"
                compact
              />
            </ul>
            <p v-else class="side-card__empty">Aucune contribution pour le moment.</p>
          </q-card>
        </aside>
      </q-tab-panel>

      <q-tab-panel v-if="events.length" name="evenements" class="profile-panel">
        <q-card flat tag="section" class="rimef-card profile-list">
          <div class="profile-list__head">
            <h2>Événements à venir</h2>
            <p>{{ eventsCount }}</p>
          </div>
          <ul class="profile-events profile-list__rows">
            <MemberEventItem v-for="event in events" :key="event.id" :event="event" wide />
          </ul>
        </q-card>
      </q-tab-panel>

      <q-tab-panel v-if="resources.length" name="ressources" class="profile-panel">
        <q-card flat tag="section" class="rimef-card profile-list">
          <div class="profile-list__head">
            <h2>Contributions</h2>
            <p>Publications et prises de parole de {{ profile.name }}</p>
          </div>
          <ul class="profile-resources profile-list__rows">
            <ResourceRow
              v-for="resource in resources"
              :key="resource.id"
              :slug="resource.slug"
              :title="resource.title"
              :type="resource.type"
              :published-on="resource.published_on"
              :source="resource.source"
              :eyebrow="resourceEyebrow(resource)"
              :note="resource.event?.title ?? null"
            >
              <template v-if="resource.event" #note>
                Présenté au
                <router-link :to="{ name: 'event', params: { slug: resource.event.slug } }">{{
                  resource.event.title
                }}</router-link>
              </template>
            </ResourceRow>
          </ul>
        </q-card>
      </q-tab-panel>

      <!-- Chargé à la première ouverture de l'onglet (panneaux non conservés par q-tab-panels) -->
      <q-tab-panel v-if="conversation" name="messages" class="profile-panel profile-messages">
        <ConversationThread
          :conversation-id="conversation.id"
          :contact-name="profile.name"
          @read="unread = 0"
        />
      </q-tab-panel>
    </q-tab-panels>
  </div>
</template>

<style lang="scss" scoped>
.profile-sheet {
  &__tabs {
    border-bottom: 1px solid var(--border);
  }

  // Sans débordement masqué : la colonne latérale reste collée au défilement (sticky)
  &__panels {
    overflow: visible;
    background: transparent;

    :deep(.q-panel) {
      overflow: visible;
    }
  }
}

// Onglets de la maquette : texte 13 px, actif en pétrole graisse 600, soulignement 2 px
.profile-tabs {
  color: var(--ink-2);

  :deep(.q-tab) {
    min-height: 50px;
    padding: 0 12px;
  }

  :deep(.q-tab:first-child) {
    padding-left: 0;
  }

  :deep(.q-tab__label) {
    font-size: var(--fs-sm);
    font-weight: 500;
  }

  :deep(.q-tab--active .q-tab__label) {
    font-weight: 600;
  }

  :deep(.q-tab__indicator) {
    height: 2px;
  }

  :deep(.q-tabs__content) {
    gap: 24px;
  }

  // Nombre d'événements (maquette .tabs .n)
  &__count {
    margin-left: 6px;
    font-weight: 400;
    color: var(--muted);
  }

  // Non-lus : pastille terracotta comme le compteur de « Mes messages »
  &__badge {
    margin-left: 6px;
    background: var(--terracotta-ink);
    color: var(--paper);
    font-size: 11px;
    font-weight: 600;
  }
}

.profile-events,
.profile-resources {
  margin: 0;
  padding: 0;
  list-style: none;
}

// « Présenté au Paris Peace Forum » : lien vers la fiche de l'événement
.profile-resources a {
  color: var(--petrol);
  text-decoration: underline;
  text-decoration-color: rgba(35, 76, 85, 0.35);
  text-underline-offset: 3px;
}

// Onglet Événements (maquette .p-panel-wide, .p-list-head, .p-list)
.profile-list {
  &__head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 4px 16px;
    padding: 22px 30px 4px;

    h2 {
      font-size: 1.6rem;
    }

    p {
      font-size: var(--fs-sm);
      color: var(--muted);
    }
  }

  &__rows {
    padding: 8px 30px;
  }
}

.q-tab-panel {
  padding: 28px 0 0;
}

.profile-body {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 360px;
  gap: 24px;
  align-items: start;
}

.profile-main {
  display: grid;
  gap: 30px;
  padding: 34px 38px 38px;

  &__empty {
    color: var(--ink-2);
  }
}

.profile-links {
  display: grid;
  gap: 10px;
  margin: 0;
  padding: 0;
  list-style: none;

  li {
    display: grid;
    grid-template-columns: 18px 130px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    min-height: 32px;

    .q-icon {
      color: var(--petrol);
    }
  }

  &__label {
    font-size: var(--fs-sm);
    color: var(--muted);
  }

  a {
    overflow: hidden;
    color: var(--petrol);
    text-overflow: ellipsis;
    white-space: nowrap;

    &:hover {
      color: var(--link-hover);
    }
  }
}

@media (max-width: 719px) {
  .profile-links li {
    grid-template-columns: 18px minmax(0, 1fr);

    a {
      grid-column: 2;
    }
  }
}

.profile-section {
  h2 {
    margin-bottom: 12px;
    font-size: 1.45rem;
  }

  & + & {
    padding-top: 26px;
    border-top: 1px solid var(--border);
  }

  // Retours à la ligne saisis par la médiatrice conservés
  &__bio {
    max-width: 66ch;
    line-height: 1.75;
    color: var(--ink-2);
    white-space: pre-line;
  }
}

.profile-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0;
  padding: 0;
  list-style: none;
}

// Étiquette pétrole claire (maquette .chip-s)
.profile-chip.q-chip {
  min-height: 30px;
  height: auto;
  margin: 0;
  padding: 4px 12px;
  border-radius: var(--radius-pill);
  background: var(--petrol-light);
  font-size: var(--fs-sm);
  line-height: 1.3;
  color: var(--petrol);
}

.profile-side {
  position: sticky;
  top: calc(var(--header-h) + 20px + env(safe-area-inset-top, 0px));
  display: grid;
  gap: 20px;
}

.side-card {
  padding: 24px 24px 20px;

  .card-head {
    margin-bottom: 14px;
  }

  .card-title {
    font-size: 1.3rem;
  }

  &__empty {
    font-size: var(--fs-sm);
    color: var(--muted);
  }
}

// Tablette : colonne sous le profil, ses deux cartes côte à côte
@media (max-width: 1079px) {
  .profile-body {
    grid-template-columns: 1fr;
  }

  .profile-side {
    position: static;
    grid-template-columns: 1fr 1fr;
    align-items: start;
  }

  .profile-main {
    padding: 26px 28px 30px;
  }
}

// Téléphone : onglets sur toute la largeur, collés sous le bandeau au défilement
@media (max-width: 719px) {
  .profile-sheet__tabs {
    position: sticky;
    top: calc(var(--header-h) + env(safe-area-inset-top, 0px));
    z-index: 20;
    background: var(--ivory);
  }

  .profile-tabs {
    :deep(.q-tabs__content) {
      gap: 0;
    }

    :deep(.q-tab) {
      flex: 1;
    }

    :deep(.q-tab:first-child) {
      padding-left: 12px;
    }
  }

  .q-tab-panel {
    padding-top: 18px;
  }

  .profile-body {
    gap: 16px;
  }

  .profile-main {
    gap: 24px;
    padding: 22px 18px 24px;
  }

  .profile-section h2 {
    font-size: 1.3rem;
  }

  .profile-chip.q-chip {
    min-height: 28px;
    font-size: var(--fs-xs);
  }

  .profile-side {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .side-card {
    padding: 18px 16px 16px;
  }

  .profile-list__head,
  .profile-list__rows {
    padding-inline: 16px;
  }
}
</style>
