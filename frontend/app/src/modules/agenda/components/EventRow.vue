<script setup lang="ts">
// Ligne d'événement (maquette Agenda .ev-row) : grande pastille date, titre, lieu et type ;
// participation de la personne connectée = liseré sauge à gauche et coche sur la date
// (sans ligne de texte en plus, qui décalait la ligne) ; à droite les médiatrices présentes (3 avatars et « +X ») et les
// actions « Voir l'événement » / « J'y participe ». Toute la ligne mène à la fiche (lien étiré).
// - hideMine : sans liseré ni coche, quand toute la liste est déjà celle de ses événements.
import { computed } from 'vue';

import AppButton from '@/components/AppButton.vue';
import DateTile from '@/components/DateTile.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { dayAndMonth } from '@/lib/dates';
import { mediatorNoun } from '@/lib/wording';
import { dayCount, type AgendaEvent } from '../services/events';

const props = withDefaults(defineProps<{ event: AgendaEvent; hideMine?: boolean }>(), {
  hideMine: false,
});
const emit = defineEmits<{ toggleParticipation: [] }>();

const tile = computed(() => {
  const { day, month } = dayAndMonth(props.event.starts_at);
  const days = dayCount(props.event);
  return { day: day.padStart(2, '0'), month, note: days > 1 ? `${days} jours` : undefined };
});

const hiddenCount = computed(() => props.event.attendee_count - props.event.attendees.length);

const peopleText = computed(() => {
  const count = props.event.attendee_count;
  const verb = props.event.is_past
    ? count > 1
      ? 'y ont participé'
      : 'y a participé'
    : count > 1
      ? 'participent'
      : 'participe';
  return `${mediatorNoun(count)} ${verb}`;
});

const to = computed(() => ({ name: 'event', params: { slug: props.event.slug } }));
const markMine = computed(() => props.event.is_participating && !props.hideMine);
</script>

<template>
  <q-card flat tag="article" class="rimef-card event-row" :class="{ 'event-row--mine': markMine }">
    <div class="event-row__date">
      <DateTile
        size="xl"
        :day="tile.day"
        :month="tile.month"
        :note="tile.note"
        :past="event.is_past"
      />
      <span v-if="markMine" class="event-row__check" aria-hidden="true">
        <q-icon name="check" size="12px" />
      </span>
    </div>

    <div class="event-row__info">
      <h3 class="event-row__title">
        <router-link :to="to">{{ event.title }}</router-link>
      </h3>
      <ul class="event-row__facts">
        <li v-if="event.place" class="meta">
          <q-icon :name="event.is_online ? 'world' : 'map-pin'" size="15px" />{{ event.place }}
        </li>
        <li v-if="event.type" class="meta">
          <q-icon name="tag" size="15px" />{{ event.type.label }}
        </li>
      </ul>
      <!-- Pour les lecteurs d'écran : la coche et le liseré sont décoratifs -->
      <p v-if="event.is_participating" class="sr-only">
        {{ event.is_past ? 'Vous y étiez' : 'Vous participez' }}
      </p>
    </div>

    <div class="event-row__side">
      <p v-if="event.attendee_count" class="event-row__people">
        <span class="event-row__stack" aria-hidden="true">
          <MemberAvatar
            v-for="person in event.attendees"
            :key="person.id"
            :name="person.name"
            :photo="person.photo_url"
            :size="32"
            :title="person.name"
          />
          <span v-if="hiddenCount > 0" class="event-row__more">+{{ hiddenCount }}</span>
        </span>
        <span>
          <b>{{ event.attendee_count }}</b> {{ peopleText }}
        </span>
      </p>

      <div class="event-row__actions">
        <AppButton size="sm" label="Voir l’événement" :to="to" class="event-row__see" />
        <AppButton
          v-if="!event.is_past"
          size="sm"
          variant="outline"
          :icon="event.is_participating ? 'check' : 'calendar-plus'"
          :label="event.is_participating ? 'Je participe' : 'J’y participe'"
          :aria-pressed="event.is_participating"
          class="event-row__join"
          :class="{ 'event-row__join--on': event.is_participating }"
          @click="emit('toggleParticipation')"
        />
      </div>
    </div>
  </q-card>
</template>

<style lang="scss" scoped>
.event-row {
  position: relative;
  display: grid;
  grid-template-columns: 76px minmax(0, 1fr) 270px;
  gap: 24px;
  align-items: center;
  padding: 18px 20px;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;

  &:hover {
    border-color: var(--card-hover-border);
    box-shadow: var(--shadow-hover);
  }

  // Focus du lien étiré : contour sur toute la ligne
  &:has(.event-row__title a:focus-visible) {
    outline: 2px solid var(--saffron);
    outline-offset: 2px;
  }

  &__info {
    min-width: 0;
  }

  &__title {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.5rem;
    line-height: 1.12;
    color: var(--ink);

    a {
      color: inherit;
      text-decoration: none;

      &:hover {
        color: var(--petrol);
      }

      // Ligne entièrement cliquable
      &::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 1;
      }

      &:focus-visible {
        outline: none;
      }
    }
  }

  &__facts {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 18px;
    margin: 8px 0 0;
    padding: 0;
    list-style: none;

    .meta {
      font-size: var(--fs-sm);
    }
  }

  // Participation : liseré sauge de 3 px (ombre intérieure, sans décaler le contenu)
  &--mine {
    box-shadow:
      inset 3px 0 0 var(--sage),
      var(--shadow-card);

    &:hover {
      box-shadow:
        inset 3px 0 0 var(--sage),
        var(--shadow-hover);
    }
  }

  &__date {
    position: relative;
    justify-self: start;
  }

  // Coche de participation au coin de la pastille ; pétrole sur la pastille sauge d'un événement passé
  &__check {
    position: absolute;
    top: -6px;
    right: -6px;
    display: grid;
    place-items: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--sage);
    box-shadow: 0 0 0 2px var(--card);
    color: var(--paper);

    .event-row:has(.date-tile--past) & {
      background: var(--petrol);
    }
  }

  // Au-dessus du lien étiré : avatars et boutons restent cliquables
  &__side {
    position: relative;
    z-index: 2;
    display: grid;
    gap: 12px;
    justify-items: start;
  }

  &__people {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: var(--fs-xs);
    color: var(--ink-2);

    b {
      font-weight: 600;
      color: var(--ink);
      font-variant-numeric: tabular-nums;
    }
  }

  &__stack {
    display: flex;
    flex: none;
    align-items: center;

    > * {
      margin-left: -8px;
      box-shadow: 0 0 0 2px var(--card);
    }

    > :first-child {
      margin-left: 0;
    }
  }

  &__more {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--ivory);
    font-size: 10.5px;
    font-weight: 600;
    color: var(--ink-2);
  }

  &__actions {
    display: flex;
    gap: 8px;
  }

  // « Voir l'événement » : bouton doux de la maquette (.btn-soft)
  &__see.app-btn {
    background: var(--petrol-light);
    color: var(--petrol);

    &:hover {
      background: #ccdddf;
    }
  }

  &__join--on.app-btn {
    border-color: transparent;
    background: var(--sage-light);
    color: var(--sage-ink);
  }
}

// Tablette : colonne de droite à sa largeur naturelle
@media (max-width: 1079px) {
  .event-row {
    grid-template-columns: 76px minmax(0, 1fr) auto;
  }
}

// Petite tablette : présentes et actions passent sous les informations
@media (max-width: 900px) {
  .event-row {
    grid-template-columns: 76px minmax(0, 1fr);
    row-gap: 14px;

    &__side {
      grid-column: 2;
      grid-template-columns: auto auto;
      justify-content: space-between;
      align-items: center;
      width: 100%;
    }
  }
}

// Téléphone : pastille réduite, faits empilés, présentes et actions en pleine largeur
@media (max-width: 719px) {
  .event-row {
    grid-template-columns: 62px minmax(0, 1fr);
    gap: 12px 16px;
    align-items: start;
    padding: 16px 14px;

    &__title {
      font-size: 1.28rem;
    }

    &__facts {
      flex-direction: column;
      gap: 2px;

      .meta {
        font-size: var(--fs-xs);
      }
    }

    &__side {
      grid-column: 1 / -1;
      grid-template-columns: 1fr;
      gap: 12px;
      padding-top: 12px;
      border-top: 1px solid var(--border);
    }

    &__actions {
      width: 100%;

      // Boutons pleine largeur et zones tactiles de 44 px
      .app-btn {
        flex: 1;
        min-height: 44px;
      }
    }
  }
}
</style>
