<script setup lang="ts">
// En-tête de la fiche (maquette .fe-head) : photo avec la pastille date (visuel aux couleurs de
// la charte sans photo), titre, type et statut, dates et lieu, aperçu des médiatrices présentes
// (ouvre l'onglet « Médiatrices présentes ») ; les actions arrivent par le slot « actions ».
import { computed, ref, watch } from 'vue';

import DateTile from '@/components/DateTile.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { dayAndMonth, formatDateRange } from '@/lib/dates';
import { LEAF_PATH, leafTransform } from '@/lib/leaf';
import { mediatorNoun } from '@/lib/wording';
import { STATUS_LABELS, type EventDetail } from '../services/events';

const props = defineProps<{ event: EventDetail }>();
const emit = defineEmits<{ showPeople: [] }>();

const photoFailed = ref(false);
watch(
  () => props.event.photo_url,
  () => (photoFailed.value = false)
);
const showPhoto = computed(() => props.event.photo_url !== null && !photoFailed.value);

const tile = computed(() => {
  const { day, month } = dayAndMonth(props.event.starts_at);
  return { day: day.padStart(2, '0'), month };
});

const dates = computed(() => formatDateRange(props.event.starts_at, props.event.ends_at));
const place = computed(() => [props.event.venue, props.event.place].filter(Boolean).join(', '));

const isPast = computed(() => props.event.status === 'past');
const preview = computed(() => props.event.attendees.slice(0, 3));
const peopleText = computed(() => {
  const count = props.event.attendee_count;
  const verb = isPast.value
    ? count > 1
      ? 'y ont participé'
      : 'y a participé'
    : count > 1
      ? 'y participent'
      : 'y participe';
  return `${mediatorNoun(count)} du réseau ${verb}`;
});
</script>

<template>
  <section class="event-header" aria-labelledby="event-title">
    <div class="event-header__photo">
      <img
        v-if="showPhoto"
        :src="event.photo_url!"
        alt=""
        width="380"
        height="247"
        decoding="async"
        fetchpriority="high"
        @error="photoFailed = true"
      />
      <svg v-else class="event-header__fallback" viewBox="0 0 380 247" aria-hidden="true">
        <path
          :d="LEAF_PATH"
          :transform="leafTransform(372, 250, -148, 230, 80)"
          fill="#C86F55"
          opacity="0.85"
        />
        <path
          :d="LEAF_PATH"
          :transform="leafTransform(376, 252, -112, 170, 60)"
          fill="#8FA89C"
          opacity="0.85"
        />
        <path
          :d="LEAF_PATH"
          :transform="leafTransform(378, 250, -84, 120, 44)"
          fill="#D3A64A"
          opacity="0.85"
        />
      </svg>
      <DateTile :day="tile.day" :month="tile.month" :past="isPast" class="event-header__date" />
    </div>

    <div class="event-header__identity">
      <h1 id="event-title">{{ event.title }}</h1>
      <div class="event-header__badges">
        <q-badge v-if="event.type" class="rimef-badge" color="primary" text-color="white">
          {{ event.type.label }}
        </q-badge>
        <q-badge
          class="rimef-badge event-header__status"
          :class="`event-header__status--${event.status}`"
        >
          {{ STATUS_LABELS[event.status] }}
        </q-badge>
      </div>

      <ul class="event-header__facts" aria-label="Date et lieu">
        <li class="meta">
          <q-icon name="calendar" size="16px" aria-hidden="true" />
          <span>{{ dates }}</span>
        </li>
        <li v-if="place" class="meta">
          <q-icon :name="event.is_online ? 'world' : 'map-pin'" size="16px" aria-hidden="true" />
          <span>{{ place }}</span>
        </li>
      </ul>

      <q-btn
        v-if="event.attendee_count"
        unelevated
        no-caps
        :ripple="false"
        class="event-header__people"
        @click="emit('showPeople')"
      >
        <span class="event-header__stack" aria-hidden="true">
          <MemberAvatar
            v-for="person in preview"
            :key="person.id"
            :name="person.name"
            :photo="person.photo_url"
            :size="30"
          />
        </span>
        <span class="event-header__people-text">
          <b>{{ event.attendee_count }}</b> {{ peopleText }}
        </span>
        <q-icon name="arrow-right" size="15px" aria-hidden="true" />
      </q-btn>
    </div>

    <div v-if="$slots.actions" class="event-header__actions">
      <slot name="actions" />
    </div>
  </section>
</template>

<style lang="scss" scoped>
.event-header {
  display: grid;
  grid-template-columns: 380px minmax(0, 1fr) auto;
  gap: 36px;
  align-items: start;
  padding-block: 22px 30px;

  &__photo {
    position: relative;
    aspect-ratio: 4 / 2.6;
    overflow: hidden;
    border-radius: var(--radius-sm);
    background: var(--petrol-dark);
    box-shadow: var(--shadow-card);

    img,
    .event-header__fallback {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }

  &__date {
    position: absolute;
    bottom: 14px;
    left: 14px;
    box-shadow: 0 6px 18px -8px rgba(23, 54, 60, 0.5);
  }

  &__identity {
    min-width: 0;
    padding-top: 4px;

    h1 {
      font-size: var(--fs-h1);
      line-height: 1.02;
      letter-spacing: -0.012em;
    }
  }

  &__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
  }

  // Statut précédé d'une pastille (maquette .badge-dot)
  &__status.q-badge {
    &::before {
      content: '';
      width: 6px;
      height: 6px;
      margin-right: 6px;
      border-radius: 50%;
      background: currentColor;
    }

    &--upcoming {
      background: var(--sage-light);
      color: var(--sage-ink);
    }

    &--ongoing {
      background: var(--terracotta-light);
      color: var(--terracotta-ink);
    }

    &--past {
      background: var(--ivory);
      color: var(--ink-2);
    }
  }

  &__facts {
    display: grid;
    gap: 6px;
    margin: 16px 0 0;
    padding: 0;
    list-style: none;

    .meta {
      font-size: var(--fs-body);

      .q-icon {
        color: var(--petrol);
      }
    }
  }

  // Aperçu « du réseau » (maquette .fe-net) : pilule paper, avatars, nombre en pétrole
  &__people.q-btn {
    min-height: 0;
    margin-top: 18px;
    padding: 8px 14px 8px 8px;
    border: 1px solid var(--border);
    border-radius: var(--radius-pill);
    background: var(--paper);
    font-size: var(--fs-sm);
    font-weight: 400;
    color: var(--ink-2);
    text-align: left;

    :deep(.q-focus-helper) {
      display: none;
    }

    :deep(.q-btn__content) {
      flex-wrap: nowrap;
      gap: 12px;
    }

    &:hover {
      border-color: var(--border-hover);
    }

    b {
      font-weight: 600;
      color: var(--petrol);
    }

    .q-icon {
      color: var(--petrol);
    }
  }

  &__stack {
    display: flex;

    > * + * {
      margin-left: -8px;
    }

    :deep(.member-avatar) {
      box-shadow: 0 0 0 2px var(--paper);
    }
  }

  &__actions {
    padding-top: 70px;
  }
}

// Tablette : photo réduite, actions sous l'en-tête
@media (max-width: 1079px) {
  .event-header {
    grid-template-columns: 300px minmax(0, 1fr);
    gap: 28px;

    &__actions {
      grid-column: 1 / -1;
      padding-top: 0;
    }
  }
}

// Téléphone : une colonne ; les actions passent dans la barre du bas
@media (max-width: 719px) {
  .event-header {
    grid-template-columns: 1fr;
    gap: 18px;
    padding-block: 14px 20px;

    &__identity h1 {
      font-size: 2.2rem;
    }

    &__facts .meta {
      font-size: var(--fs-sm);
    }

    &__actions {
      display: none;
    }
  }
}

@media (min-width: 720px) {
  .event-header__people-text {
    white-space: nowrap;
  }
}
</style>
