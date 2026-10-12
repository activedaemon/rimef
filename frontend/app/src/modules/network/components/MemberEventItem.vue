<script setup lang="ts">
// Événement à venir d'une médiatrice (maquette Profil) : pastille date, titre (lien vers la
// fiche), lieu et rôle (Intervenante en terracotta, Participante en sauge, comme sur la fiche
// événement). « wide » : ligne de l'onglet Événements en trois colonnes (surtitre « Type ·
// année », titre et lieu ; résumé ; rôle et « Voir l'événement ») ; sinon ligne compacte de la
// carte « Prochains événements ».
import { computed } from 'vue';

import DateTile from '@/components/DateTile.vue';
import { dayAndMonth } from '@/lib/dates';
import type { MemberEvent } from '../services/members';

const props = withDefaults(defineProps<{ event: MemberEvent; wide?: boolean }>(), {
  wide: false,
});

const tile = computed(() => {
  const { day, month } = dayAndMonth(props.event.starts_at);
  return { day: day.padStart(2, '0'), month };
});
const eyebrow = computed(() =>
  [props.event.type?.label, new Date(props.event.starts_at).getFullYear()]
    .filter(Boolean)
    .join(' · ')
);
const to = computed(() => ({ name: 'event', params: { slug: props.event.slug } }));
const isSpeaker = computed(() => props.event.role.value === 'speaker');
</script>

<template>
  <li class="member-event" :class="{ 'member-event--wide': wide }">
    <DateTile :day="tile.day" :month="tile.month" class="member-event__date" />
    <div class="member-event__info">
      <p v-if="wide" class="eyebrow member-event__eyebrow">{{ eyebrow }}</p>
      <h3>
        <router-link :to="to">{{ event.title }}</router-link>
      </h3>
      <p v-if="event.place" class="meta member-event__place">
        <q-icon :name="event.is_online ? 'world' : 'map-pin'" size="13px" />{{ event.place }}
      </p>
      <q-badge
        v-if="!wide"
        class="rimef-badge member-event__role"
        :class="{ 'member-event__role--speaker': isSpeaker }"
      >
        {{ event.role.label }}
      </q-badge>
    </div>
    <p v-if="wide" class="member-event__summary">{{ event.summary }}</p>
    <div v-if="wide" class="member-event__side">
      <q-badge
        class="rimef-badge member-event__role"
        :class="{ 'member-event__role--speaker': isSpeaker }"
      >
        {{ event.role.label }}
      </q-badge>
      <router-link :to="to" class="link-more">Voir l’événement</router-link>
    </div>
  </li>
</template>

<style lang="scss" scoped>
// Ligne aérée de la carte (audit visuel de la maquette : sans boîte, filet entre les lignes)
.member-event {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 14px 0;

  &:first-child {
    padding-top: 4px;
  }

  & + & {
    border-top: 1px solid var(--border);
  }

  // Pastille un peu plus haute que la taille standard (60 px)
  &__date.date-tile {
    height: 68px;
  }

  &__info {
    min-width: 0;
  }

  h3 {
    // noinspection CssNoGenericFontName
    font-family: var(--font-ui);
    font-size: var(--fs-sm);
    font-weight: 600;
    line-height: 1.35;

    a {
      color: inherit;
      text-decoration: none;

      &:hover,
      &:focus-visible {
        color: var(--petrol);
      }
    }
  }

  &__place {
    margin-top: 3px;
    font-size: var(--fs-xs);
  }

  &__role.q-badge {
    margin-top: 8px;
    background: var(--sage-light);
    color: var(--sage-ink);
  }

  &__role--speaker.q-badge {
    background: var(--terracotta-light);
    color: var(--terracotta-ink);
  }

  // Ligne de l'onglet Événements (maquette .row)
  &--wide {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) minmax(0, 1.2fr) auto;
    align-items: center;
    gap: 20px;
    padding-block: 18px;

    &:first-child {
      padding-top: 18px;
    }

    h3 {
      // noinspection CssNoGenericFontName
      font-family: var(--font-serif);
      font-size: 1.35rem;
      font-weight: 400;
    }

    .member-event__place {
      margin-top: 5px;
      font-size: var(--fs-sm);
    }

    .member-event__role {
      margin-top: 0;
    }
  }

  &__eyebrow {
    color: var(--muted);
  }

  // Colonne du milieu, séparée du titre par un filet discret (vide sans résumé)
  &__summary {
    align-self: stretch;
    display: flex;
    align-items: center;
    padding-left: 20px;
    border-left: 1px solid var(--border);
    font-size: var(--fs-sm);
    line-height: 1.55;
    color: var(--ink-2);

    &:empty {
      border-left-color: transparent;
    }
  }

  &__side {
    display: flex;
    align-items: center;
    gap: 12px;
  }
}

// Tablette et téléphone : le résumé passe sous le titre et le lieu
@media (max-width: 1079px) {
  .member-event--wide {
    grid-template-columns: auto minmax(0, 1fr) auto;
    row-gap: 8px;

    .member-event__summary {
      grid-column: 2;
      grid-row: 2;
      padding-left: 0;
      border-left: 0;

      &:empty {
        display: none;
      }
    }

    .member-event__side {
      grid-row: 1;
      grid-column: 3;
    }
  }
}

@media (max-width: 719px) {
  .member-event--wide {
    grid-template-columns: auto 1fr;
    gap: 14px;

    .member-event__summary {
      grid-column: 2;
      grid-row: auto;
    }

    h3 {
      font-size: 1.2rem;
    }

    .member-event__side {
      grid-row: auto;
      grid-column: 2;
    }
  }
}
</style>
