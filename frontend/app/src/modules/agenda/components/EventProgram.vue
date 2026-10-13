<script setup lang="ts">
// Programme d'un événement (maquette .prog-day / .slot) : un bloc par jour (« Mardi 10 novembre »,
// « Jour 1 »), créneaux à l'heure de l'appareil ; ceux du réseau sont surlignés en terracotta,
// avec l'intervenante du réseau.
import MemberAvatar from '@/components/MemberAvatar.vue';
import type { ProgramDay } from '../services/events';

defineProps<{ days: ProgramDay[] }>();

/** « 2026-11-10 » → « Mardi 10 novembre ». */
function dayTitle(date: string): string {
  const [year, month, day] = date.split('-').map(Number);
  const label = new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  }).format(new Date(year!, month! - 1, day!));
  return label.charAt(0).toUpperCase() + label.slice(1);
}
</script>

<template>
  <div class="event-program">
    <section
      v-for="(day, index) in days"
      :key="day.date"
      class="event-program__day"
      :aria-labelledby="`program-${day.date}`"
    >
      <h3 :id="`program-${day.date}`">
        {{ dayTitle(day.date) }}
        <small v-if="days.length > 1">Jour {{ index + 1 }}</small>
      </h3>
      <div
        v-for="session in day.sessions"
        :key="session.id"
        class="event-program__slot"
        :class="{ 'event-program__slot--network': session.is_network }"
      >
        <time :datetime="`${day.date}T${session.time}`">{{ session.time }}</time>
        <div>
          <h4>{{ session.title }}</h4>
          <p v-if="session.detail">{{ session.detail }}</p>
          <div v-if="session.speaker" class="event-program__speaker">
            <MemberAvatar
              :name="session.speaker.name"
              :photo="session.speaker.photo_url"
              :size="24"
            />
            <span>
              <router-link :to="{ name: 'member', params: { slug: session.speaker.slug } }">
                {{ session.speaker.name }}</router-link
              >
              intervient pour le RIMeF
            </span>
          </div>
        </div>
      </div>
    </section>
    <p class="event-program__note">
      Programme indicatif. Consultez le programme officiel pour les horaires définitifs.
    </p>
  </div>
</template>

<style lang="scss" scoped>
.event-program {
  &__day + &__day {
    margin-top: 26px;
  }

  h3 {
    display: flex;
    align-items: baseline;
    gap: 10px;
    margin-bottom: 6px;
    font-size: 1.3rem;

    small {
      // noinspection CssNoGenericFontName
      font-family: var(--font-ui);
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }

  &__slot {
    display: grid;
    grid-template-columns: 70px minmax(0, 1fr);
    gap: 18px;
    padding: 14px 0;
    border-top: 1px solid var(--border);

    time {
      font-size: var(--fs-sm);
      font-weight: 600;
      font-variant-numeric: tabular-nums;
      color: var(--petrol);
    }

    h4 {
      margin: 0;
      font-size: var(--fs-body);
      font-weight: 600;
      line-height: 1.4;
      color: var(--ink);
    }

    p {
      margin-top: 3px;
      font-size: var(--fs-sm);
      color: var(--muted);
    }

    &--network {
      margin-inline: -12px;
      padding-inline: 12px;
      border-top-color: transparent;
      border-radius: var(--radius-xs);
      background: linear-gradient(90deg, var(--terracotta-light), transparent 70%);
    }
  }

  &__speaker.event-program__speaker {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
    font-size: var(--fs-xs);
    color: var(--ink-2);

    a {
      font-weight: 500;
      color: var(--ink);
      text-decoration: none;

      &:hover,
      &:focus-visible {
        color: var(--petrol);
      }
    }
  }

  &__note {
    margin-top: 18px;
    font-size: var(--fs-xs);
    color: var(--muted);
  }
}

@media (max-width: 719px) {
  .event-program__slot {
    grid-template-columns: 54px minmax(0, 1fr);
    gap: 12px;
  }
}
</style>
