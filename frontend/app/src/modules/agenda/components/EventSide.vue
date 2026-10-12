<script setup lang="ts">
// Colonne de la fiche (maquette .fe-side) : organisateur et liens utiles, puis « En pratique »
// (dates, lieu, format, langues) ; chaque information absente est omise.
import { computed } from 'vue';

import { formatDateRange } from '@/lib/dates';
import type { EventDetail } from '../services/events';

const props = defineProps<{ event: EventDetail }>();

const facts = computed(() =>
  [
    { label: 'Dates', value: formatDateRange(props.event.starts_at, props.event.ends_at) },
    {
      label: 'Lieu',
      value: [props.event.venue, props.event.place].filter(Boolean).join(', ') || null,
    },
    { label: 'Format', value: props.event.is_online ? 'En ligne' : 'En présentiel' },
    { label: 'Langues', value: props.event.languages },
  ].filter((fact): fact is { label: string; value: string } => Boolean(fact.value))
);

const hasOrganizerCard = computed(() => props.event.organizer || props.event.links.length);
</script>

<template>
  <aside class="event-side" aria-label="Informations pratiques">
    <q-card v-if="hasOrganizerCard" flat tag="section" class="rimef-card event-side__card">
      <template v-if="event.organizer">
        <h2>Organisateur</h2>
        <div class="event-side__organizer">
          <span class="event-side__mark" aria-hidden="true">
            <q-icon name="building-community" size="22px" />
          </span>
          <span>
            <b>{{ event.organizer.name }}</b>
            <small v-if="event.organizer.detail">{{ event.organizer.detail }}</small>
          </span>
        </div>
      </template>

      <template v-if="event.links.length">
        <q-separator v-if="event.organizer" class="event-side__separator" />
        <h2>Liens utiles</h2>
        <ul class="event-side__links">
          <li v-for="link in event.links" :key="link.url">
            <a :href="link.url" target="_blank" rel="noopener">
              <q-icon name="world-www" size="16px" aria-hidden="true" />
              {{ link.label }}
              <span class="sr-only">(nouvel onglet)</span>
              <q-icon
                name="external-link"
                size="12px"
                class="event-side__external"
                aria-hidden="true"
              />
            </a>
          </li>
        </ul>
      </template>
    </q-card>

    <q-card flat tag="section" class="rimef-card event-side__card">
      <h2>En pratique</h2>
      <dl class="event-side__facts">
        <template v-for="fact in facts" :key="fact.label">
          <dt>{{ fact.label }}</dt>
          <dd>{{ fact.value }}</dd>
        </template>
      </dl>
    </q-card>
  </aside>
</template>

<style lang="scss" scoped>
.event-side {
  position: sticky;
  top: calc(var(--header-h) + 20px + env(safe-area-inset-top, 0px));
  display: grid;
  gap: 20px;

  &__card {
    padding: 22px 22px 20px;

    h2 {
      // noinspection CssNoGenericFontName
      margin-bottom: 12px;
      font-family: var(--font-ui);
      font-size: var(--fs-sm);
      font-weight: 600;
      color: var(--ink);
    }
  }

  &__organizer {
    display: flex;
    align-items: center;
    gap: 12px;

    b {
      display: block;
      font-weight: 500;
    }

    small {
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }

  &__mark {
    display: grid;
    flex: none;
    place-items: center;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--petrol-light);
    color: var(--petrol);
  }

  &__separator {
    margin: 18px 0;
    background: var(--border);
  }

  &__links {
    display: grid;
    gap: 4px;
    margin: 0;
    padding: 0;
    list-style: none;

    a {
      display: flex;
      align-items: center;
      gap: 10px;
      min-height: 32px;
      padding: 7px 0;
      font-size: var(--fs-sm);
      color: var(--petrol);
      text-decoration: underline;
      text-decoration-color: rgba(35, 76, 85, 0.35);
      text-underline-offset: 3px;

      &:hover {
        text-decoration-color: var(--petrol);
      }

      .q-icon {
        color: var(--muted);
      }
    }
  }

  &__external {
    margin-left: auto;
  }

  &__facts {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 8px 16px;
    margin: 0;
    font-size: var(--fs-sm);

    dt {
      color: var(--muted);
    }

    dd {
      margin: 0;
      color: var(--ink);
    }
  }
}

// Tablette : sous le contenu, les deux cartes côte à côte
@media (max-width: 1079px) {
  .event-side {
    position: static;
    grid-template-columns: 1fr 1fr;
    align-items: start;
  }
}

@media (max-width: 719px) {
  .event-side {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
</style>
