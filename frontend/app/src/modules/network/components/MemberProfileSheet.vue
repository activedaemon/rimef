<script setup lang="ts">
// Fiche d'une médiatrice (maquette Profil médiatrice) : en-tête, repères, onglets Profil /
// Événements / Ressources, et Messages quand la personne connectée a déjà échangé avec elle.
// Partagée par /reseau/:slug et « Mon profil » : les actions de l'en-tête arrivent par le
// slot « actions ». L'onglet ouvert figure dans l'URL (?onglet=messages).
// Événements et ressources ne sont pas encore reliés aux médiatrices : onglets et cartes
// de la colonne affichent un message d'attente.
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import EmptyState from '@/components/EmptyState.vue';
import ConversationThread from '@modules/messages/components/ConversationThread.vue';
import type { MemberProfile } from '../services/members';
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

function tabFromUrl(): Tab {
  const value = route.query.onglet;
  const wanted = TABS.find((name) => name === value) ?? 'profil';
  return wanted === 'messages' && !conversation.value ? 'profil' : wanted;
}

const tab = ref<Tab>(tabFromUrl());
watch([() => route.query.onglet, conversation], () => (tab.value = tabFromUrl()));
watch(tab, (value) => {
  const onglet = value === 'profil' ? undefined : value;
  if (route.query.onglet !== onglet) void router.replace({ query: { ...route.query, onglet } });
});

const messagesLabel = computed(() =>
  unread.value > 0
    ? `Messages, ${unread.value} ${unread.value > 1 ? 'non lus' : 'non lu'}`
    : 'Messages'
);

const isEmpty = computed(
  () => !props.profile.bio && !props.profile.expertises.length && !props.profile.zones.length
);
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
          <q-tab name="evenements" label="Événements" />
          <q-tab name="ressources" label="Ressources" />
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
        </q-card>

        <aside class="profile-side" :aria-label="`En lien avec ${profile.name}`">
          <q-card flat tag="section" class="rimef-card side-card" aria-labelledby="profile-next">
            <div class="card-head">
              <h2 id="profile-next" class="card-title">Prochains événements</h2>
            </div>
            <p class="side-card__empty">Aucun événement à venir pour le moment.</p>
          </q-card>
          <q-card
            flat
            tag="section"
            class="rimef-card side-card"
            aria-labelledby="profile-contributions"
          >
            <div class="card-head">
              <h2 id="profile-contributions" class="card-title">Contributions récentes</h2>
            </div>
            <p class="side-card__empty">Aucune contribution pour le moment.</p>
          </q-card>
        </aside>
      </q-tab-panel>

      <q-tab-panel name="evenements" class="profile-panel">
        <EmptyState
          icon="calendar-event"
          title="Les événements arrivent bientôt"
          :text="`Les rendez-vous auxquels ${profile.first_name} participe seront affichés ici.`"
        />
      </q-tab-panel>

      <q-tab-panel name="ressources" class="profile-panel">
        <EmptyState
          icon="book"
          title="Les ressources arrivent bientôt"
          :text="`Les publications et prises de parole de ${profile.first_name} seront affichées ici.`"
        />
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

  // Non-lus : pastille terracotta comme le compteur de « Mes messages »
  &__badge {
    margin-left: 6px;
    background: var(--terracotta-ink);
    color: var(--paper);
    font-size: 11px;
    font-weight: 600;
  }
}

.profile-messages {
  max-width: 820px;
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
}
</style>
