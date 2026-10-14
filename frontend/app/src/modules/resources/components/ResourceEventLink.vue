<script setup lang="ts">
// Événement associé à une ressource (maquette .ev-link) : grande pastille date, titre (vers la
// fiche), dates et lieu, ce qui relie la ressource à l'événement, médiatrices qui y participent,
// « Voir l'événement ».
import { computed } from 'vue';

import AppButton from '@/components/AppButton.vue';
import DateTile from '@/components/DateTile.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { dayAndMonth, formatDateRange } from '@/lib/dates';
import { mediatorNoun } from '@/lib/wording';
import type { ResourceDetail } from '../services/resources';

const props = defineProps<{ event: NonNullable<ResourceDetail['event']> }>();

const to = computed(() => ({ name: 'event', params: { slug: props.event.slug } }));
const tile = computed(() => {
  const { day, month } = dayAndMonth(props.event.starts_at);
  return { day: day.padStart(2, '0'), month };
});
const dates = computed(() => formatDateRange(props.event.starts_at, props.event.ends_at));
const hidden = computed(() => props.event.attendee_count - props.event.attendees.length);
const isPast = computed(() => new Date(props.event.ends_at ?? props.event.starts_at) < new Date());
const peopleText = computed(() => {
  const count = props.event.attendee_count;
  const verb = isPast.value
    ? count > 1
      ? 'y ont participé'
      : 'y a participé'
    : count > 1
      ? 'y participent'
      : 'y participe';
  return `${mediatorNoun(count)} ${verb}`;
});
</script>

<template>
  <div class="resource-event">
    <DateTile :day="tile.day" :month="tile.month" size="xl" :past="isPast" />

    <div class="resource-event__body">
      <h3>
        <router-link :to="to">{{ event.title }}</router-link>
      </h3>
      <ul class="resource-event__facts">
        <li class="meta">
          <q-icon name="calendar" size="15px" aria-hidden="true" />
          <span>{{ dates }}</span>
        </li>
        <li v-if="event.place" class="meta">
          <q-icon name="map-pin" size="15px" aria-hidden="true" />
          <span>{{ event.place }}</span>
        </li>
      </ul>
      <p v-if="event.note" class="resource-event__note">{{ event.note }}</p>
    </div>

    <div class="resource-event__side">
      <p v-if="event.attendee_count" class="resource-event__people">
        <span class="resource-event__stack" aria-hidden="true">
          <MemberAvatar
            v-for="person in event.attendees"
            :key="person.id"
            :name="person.name"
            :photo="person.photo_url"
            :size="28"
          />
          <span v-if="hidden > 0" class="resource-event__more">+{{ hidden }}</span>
        </span>
        <span>
          <b>{{ event.attendee_count }}</b> {{ peopleText }}
        </span>
      </p>
      <AppButton variant="outline" size="sm" label="Voir l’événement" :to="to" />
    </div>
  </div>
</template>

<style lang="scss" scoped>
.resource-event {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  gap: 22px;
  align-items: center;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--paper);

  h3 {
    font-size: 1.5rem;
    line-height: 1.1;

    a {
      color: inherit;
      text-decoration: none;

      &:hover,
      &:focus-visible {
        color: var(--petrol);
      }
    }
  }

  &__facts {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 16px;
    margin: 6px 0 0;
    padding: 0;
    list-style: none;

    .q-icon {
      color: var(--petrol);
    }
  }

  &__note {
    margin-top: 10px;
    padding: 8px 12px;
    border-radius: var(--radius-xs);
    background: linear-gradient(90deg, var(--terracotta-light), transparent 85%);
    font-size: var(--fs-sm);
    color: var(--ink-2);
  }

  &__side {
    display: grid;
    gap: 12px;
    justify-items: end;
  }

  &__people {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: var(--fs-xs);
    color: var(--ink-2);

    b {
      font-weight: 600;
      color: var(--ink);
    }
  }

  &__stack {
    display: flex;
    align-items: center;

    > * + * {
      margin-left: -7px;
    }

    :deep(.member-avatar),
    .resource-event__more {
      box-shadow: 0 0 0 2px var(--paper);
    }
  }

  &__more {
    display: grid;
    place-items: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--ivory);
    font-size: 10.5px;
    font-weight: 600;
    color: var(--ink-2);
  }
}

// Téléphone : pastille et texte côte à côte, participantes et bouton dessous
@media (max-width: 719px) {
  .resource-event {
    grid-template-columns: auto minmax(0, 1fr);
    gap: 14px;

    &__side {
      grid-column: 1 / -1;
      justify-items: start;
    }
  }
}
</style>
