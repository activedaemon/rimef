<script setup lang="ts">
// Fiche d'un événement (/agenda/paris-peace-forum-2026, maquette Fiche événement) : en-tête et
// actions, onglets À propos / Programme / Médiatrices présentes / Ressources liées (onglet
// ouvert dans l'URL, ?onglet=programme), colonne d'informations pratiques. Sur téléphone, les
// actions passent dans une barre fixée au-dessus de la barre d'onglets. L'enveloppe d'une
// médiatrice ouvre la fenêtre de message sur place, sans quitter la fiche.
// Les ressources ne sont pas encore reliées aux événements : l'onglet affiche un message d'attente.
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import BackLink from '@/components/BackLink.vue';
import EmptyState from '@/components/EmptyState.vue';
import { mediatorNoun } from '@/lib/wording';
import ContactDialog from '@modules/network/components/ContactDialog.vue';
import EventActions from '../components/EventActions.vue';
import EventAttendeeCard from '../components/EventAttendeeCard.vue';
import EventHeader from '../components/EventHeader.vue';
import EventNetwork from '../components/EventNetwork.vue';
import EventProgram from '../components/EventProgram.vue';
import EventSide from '../components/EventSide.vue';
import { useEventPage } from '../composables/useEventPage';
import type { EventAttendee } from '../services/events';

const route = useRoute();
const router = useRouter();
const { event, loading, notFound, toggleParticipation, toggleMeeting } = useEventPage(() =>
  typeof route.params.slug === 'string' ? route.params.slug : null
);

type Tab = 'a-propos' | 'programme' | 'mediatrices' | 'ressources';
const TABS: Tab[] = ['a-propos', 'programme', 'mediatrices', 'ressources'];

function tabFromUrl(): Tab {
  return TABS.find((name) => name === route.query.onglet) ?? 'a-propos';
}

const tab = ref<Tab>(tabFromUrl());
watch(
  () => route.query.onglet,
  () => (tab.value = tabFromUrl())
);
watch(tab, (value) => {
  const onglet = value === 'a-propos' ? undefined : value;
  if (route.query.onglet !== onglet) void router.replace({ query: { ...route.query, onglet } });
});

const tabsRef = ref<HTMLElement | null>(null);

/** Aperçus des médiatrices (en-tête, bloc du réseau) : onglet dédié, remonté en vue. */
function showPeople(): void {
  tab.value = 'mediatrices';
  tabsRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/** Médiatrice à qui écrire (fenêtre de message ouverte tant qu'elle est choisie). */
const recipient = ref<EventAttendee | null>(null);
const contactOpen = ref(false);

function write(attendee: EventAttendee): void {
  recipient.value = attendee;
  contactOpen.value = true;
}

const paragraphs = computed(() =>
  (event.value?.description ?? '')
    .split(/\n\s*\n/)
    .map((text) => text.trim())
    .filter(Boolean)
);

type RoleFilter = '' | 'speaker' | 'participant';
const roleFilter = ref<RoleFilter>('');
const ROLE_OPTIONS = [
  { label: 'Toutes', value: '' },
  { label: 'Intervenantes', value: 'speaker' },
  { label: 'Participantes', value: 'participant' },
];

const attendees = computed(() => event.value?.attendees ?? []);
const hasSpeakers = computed(() =>
  attendees.value.some((person) => person.role.value === 'speaker')
);
const shownAttendees = computed(() =>
  roleFilter.value
    ? attendees.value.filter((person) => person.role.value === roleFilter.value)
    : attendees.value
);
const peopleTitle = computed(() => {
  const count = attendees.value.length;
  return `${count} ${mediatorNoun(count)} du réseau ${count > 1 ? 'présentes' : 'présente'}`;
});
</script>

<template>
  <q-page class="inner-page event-page" :class="{ 'event-page--docked': event }">
    <div class="container">
      <BackLink :to="{ name: 'agenda' }" label="Retour à l’agenda" class="event-page__back" />
    </div>

    <template v-if="event">
      <div class="container">
        <EventHeader :event="event" @show-people="showPeople">
          <template #actions>
            <EventActions :event="event" @toggle-participation="toggleParticipation" />
          </template>
        </EventHeader>
      </div>

      <div ref="tabsRef" class="event-page__tabs">
        <div class="container">
          <q-tabs
            v-model="tab"
            no-caps
            align="left"
            active-color="primary"
            indicator-color="primary"
            narrow-indicator
            outside-arrows
            mobile-arrows
            class="event-tabs"
            aria-label="Sections de l’événement"
          >
            <q-tab name="a-propos" label="À propos" />
            <q-tab name="programme" label="Programme" />
            <q-tab name="mediatrices">
              <span class="q-tab__label">
                Médiatrices présentes<span class="event-tabs__count">({{ attendees.length }})</span>
              </span>
            </q-tab>
            <q-tab name="ressources" label="Ressources liées" />
          </q-tabs>
        </div>
      </div>

      <div class="container event-body">
        <q-tab-panels v-model="tab" class="event-body__panels">
          <q-tab-panel name="a-propos" class="event-body__main">
            <q-card flat tag="article" class="rimef-card event-card">
              <section class="event-card__section" aria-labelledby="event-about">
                <h2 id="event-about">À propos</h2>
                <p v-for="(text, index) in paragraphs" :key="index">{{ text }}</p>
                <p v-if="!paragraphs.length" class="event-card__empty">
                  La présentation de l’événement n’est pas encore disponible.
                </p>
              </section>
              <section
                v-if="event.themes.length"
                class="event-card__section"
                aria-labelledby="event-themes"
              >
                <h2 id="event-themes">Thématiques</h2>
                <ul class="event-chips">
                  <li v-for="theme in event.themes" :key="theme">
                    <q-chip :ripple="false" class="event-chip">{{ theme }}</q-chip>
                  </li>
                </ul>
              </section>
            </q-card>

            <EventNetwork
              v-if="attendees.length"
              :event="event"
              @show-people="showPeople"
              @toggle-meeting="toggleMeeting"
              @write="write"
            />
          </q-tab-panel>

          <q-tab-panel name="programme" class="event-body__main">
            <q-card flat tag="article" class="rimef-card event-card">
              <EventProgram v-if="event.program.length" :days="event.program" />
              <EmptyState
                v-else
                icon="list-details"
                title="Le programme n’est pas encore disponible"
                text="Il sera affiché ici dès sa publication."
              />
            </q-card>
          </q-tab-panel>

          <q-tab-panel name="mediatrices" class="event-body__main">
            <q-card flat tag="article" class="rimef-card event-card">
              <template v-if="attendees.length">
                <div class="event-people__toolbar">
                  <h2>{{ peopleTitle }}</h2>
                  <q-btn-toggle
                    v-if="hasSpeakers"
                    v-model="roleFilter"
                    :options="ROLE_OPTIONS"
                    no-caps
                    unelevated
                    :ripple="false"
                    class="event-people__filter"
                    aria-label="Filtrer par rôle"
                  />
                </div>
                <ul class="event-people__grid">
                  <li v-for="person in shownAttendees" :key="person.id">
                    <EventAttendeeCard :attendee="person" full @write="write" />
                  </li>
                </ul>
              </template>
              <EmptyState
                v-else
                icon="users-group"
                title="Aucune médiatrice du réseau pour le moment"
                :text="
                  event.status === 'past'
                    ? 'Aucune médiatrice du réseau n’y a participé.'
                    : 'Indiquez que vous y participez pour que le réseau le sache.'
                "
              />
            </q-card>
          </q-tab-panel>

          <q-tab-panel name="ressources" class="event-body__main">
            <q-card flat tag="article" class="rimef-card event-card">
              <EmptyState
                icon="book"
                title="Les ressources arrivent bientôt"
                text="Rapports, articles et podcasts liés à cet événement seront affichés ici."
              />
            </q-card>
          </q-tab-panel>
        </q-tab-panels>

        <EventSide :event="event" />
      </div>

      <ContactDialog v-if="recipient" v-model="contactOpen" :profile="recipient" />

      <q-page-sticky v-if="$q.screen.lt.sm" position="bottom" expand class="event-dock">
        <EventActions :event="event" dock @toggle-participation="toggleParticipation" />
      </q-page-sticky>
    </template>

    <div v-else-if="loading" class="container event-page__loading">
      <q-spinner size="32px" color="primary" aria-label="Chargement de l’événement" />
    </div>

    <div v-else-if="notFound" class="container event-page__not-found">
      <EmptyState
        icon="calendar-question"
        title="Événement introuvable"
        text="Cet événement n’existe pas ou n’est plus à l’agenda."
      />
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.event-page {
  &__back {
    margin-top: var(--s-4);
  }

  &__loading {
    display: grid;
    place-items: center;
    padding-block: var(--s-16);
  }

  &__not-found {
    margin-top: var(--s-4);
  }

  &__tabs {
    scroll-margin-top: var(--header-h);
    border-bottom: 1px solid var(--border);
  }
}

// Onglets de la maquette : texte 13 px, actif en pétrole graisse 600, soulignement 2 px
.event-tabs {
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

  &__count {
    margin-left: 4px;
    font-weight: 400;
    color: var(--muted);
  }
}

.event-body {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 330px;
  gap: 24px;
  align-items: start;
  padding-block: 28px 64px;

  // Sans débordement masqué : la colonne latérale reste collée au défilement (sticky)
  &__panels {
    min-width: 0;
    overflow: visible;
    background: transparent;

    :deep(.q-panel) {
      overflow: visible;
    }
  }

  &__main.q-tab-panel {
    display: grid;
    gap: 20px;
    padding: 0;
  }
}

.event-card {
  padding: 28px 32px 30px;

  &__section {
    h2 {
      margin-bottom: 12px;
      font-size: 1.45rem;
    }

    p {
      max-width: 68ch;
      line-height: 1.75;
      color: var(--ink-2);
    }

    p + p {
      margin-top: 12px;
    }

    & + & {
      margin-top: 26px;
      padding-top: 24px;
      border-top: 1px solid var(--border);
    }
  }

  &__empty {
    color: var(--muted);
  }
}

.event-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0;
  padding: 0;
  list-style: none;
}

// Étiquette pétrole claire (maquette .chip-s)
.event-chip.q-chip {
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

.event-people {
  &__toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;

    h2 {
      font-size: 1.45rem;
    }
  }

  // Sélecteur segmenté de la maquette (.seg)
  &__filter {
    padding: 3px;
    gap: 2px;
    border-radius: var(--radius-xs);
    background: var(--ivory);

    :deep(.q-btn) {
      min-height: 30px;
      padding: 0 12px;
      border-radius: var(--radius-xs);
      background: transparent;
      font-size: var(--fs-xs);
      font-weight: 500;
      color: var(--ink-2);
    }

    :deep(.q-btn .q-focus-helper) {
      display: none;
    }

    :deep(.q-btn[aria-pressed='true']) {
      background: var(--paper);
      color: var(--petrol);
      box-shadow: 0 1px 2px rgba(24, 32, 51, 0.08);
    }
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin: 0;
    padding: 0;
    list-style: none;

    :deep(.attendee-card) {
      border: 1px solid var(--border);
    }
  }
}

// Barre d'actions du téléphone, au-dessus de la barre d'onglets
.event-dock {
  z-index: 45;

  :deep(> div) {
    width: 100%;
  }

  .event-actions {
    width: 100%;
    padding: 10px var(--gutter);
    border-top: 1px solid var(--border);
    background: rgba(252, 250, 246, 0.97);
  }
}

// Tablette : colonne sous le contenu
@media (max-width: 1079px) {
  .event-body {
    grid-template-columns: 1fr;
  }
}

// Téléphone : onglets collés sous le bandeau, place pour la barre d'actions
@media (max-width: 719px) {
  .event-page--docked {
    padding-bottom: 64px;
  }

  .event-page__tabs {
    position: sticky;
    top: calc(var(--header-h) + env(safe-area-inset-top, 0px));
    z-index: 20;
    background: var(--ivory);
  }

  .event-tabs :deep(.q-tab:first-child) {
    padding-left: 12px;
  }

  .event-body {
    gap: 16px;
    padding-block: 16px 40px;
  }

  .event-card {
    padding: 22px 18px 24px;
  }

  .event-people__grid {
    grid-template-columns: 1fr;
  }
}
</style>
