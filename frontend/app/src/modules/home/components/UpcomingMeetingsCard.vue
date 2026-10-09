<script setup lang="ts">
// Prochains rendez-vous (maquette Accueil) : pastille date, titre, lieu, participantes.
import DateTile from '@/components/DateTile.vue';
import { mediatorCount } from '@/lib/wording';
import type { Meeting } from '../services/home-feed';

defineProps<{ meetings: Meeting[] }>();
</script>

<template>
  <q-card flat class="rimef-card home-block" tag="section" aria-labelledby="meetings-title">
    <div class="card-head">
      <h2 id="meetings-title" class="card-title">Prochains rendez-vous</h2>
      <router-link :to="{ name: 'agenda' }" class="link-more">Voir tout</router-link>
    </div>
    <p v-if="!meetings.length" class="home-block__empty">
      Aucun rendez-vous à venir pour le moment.
    </p>
    <ul v-else class="home-block__list">
      <li v-for="meeting in meetings" :key="meeting.id" class="meeting">
        <DateTile :day="meeting.day" :month="meeting.month" />
        <div>
          <h3 class="home-block__item-title">
            <router-link :to="{ name: 'agenda' }">{{ meeting.title }}</router-link>
          </h3>
          <p v-if="meeting.place" class="meeting__place">{{ meeting.place }}</p>
          <q-badge class="rimef-badge meeting__badge" color="sage-light" text-color="sage-ink">
            {{ mediatorCount(meeting.attendeeCount) }}
          </q-badge>
        </div>
      </li>
    </ul>
  </q-card>
</template>

<style lang="scss" scoped>
@use './home-block';

.meeting {
  display: flex;
  align-items: flex-start;
  gap: 16px;

  &__place {
    margin-top: 2px;
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &__badge {
    margin-top: 8px;
  }
}
</style>
