<script setup lang="ts">
// Événement à la une (maquette Accueil) : visuel, titre, dates, lieu, participantes,
// actions. « J'y participe » est enregistré par l'API (affiché tout de suite, annulé si refus).
import { computed, ref } from 'vue';

import AppButton from '@/components/AppButton.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import { mediatorCount } from '@/lib/wording';
import { useSession } from '@/stores/session';
import { setParticipation, type FeaturedEvent } from '../services/home-feed';

const props = defineProps<{ event: FeaturedEvent }>();

/** Avatars affichés avant le « +N ». */
const VISIBLE_ATTENDEES = 4;

const notify = useNotify();
const session = useSession();
const participating = ref(props.event.isParticipating);

// Le total de l'API compte déjà la personne connectée si elle participait au chargement
const othersCount = props.event.attendeeCount - (props.event.isParticipating ? 1 : 0);
const count = computed(() => othersCount + (participating.value ? 1 : 0));

// « Vous » en premier quand elle participe, puis les autres participantes
const others = computed(() =>
  props.event.attendees
    .filter((person) => person.id !== session.user?.id)
    .slice(0, VISIBLE_ATTENDEES - (participating.value ? 1 : 0))
);
const hiddenCount = computed(
  () => count.value - others.value.length - (participating.value ? 1 : 0)
);

async function toggleParticipation(): Promise<void> {
  participating.value = !participating.value;
  try {
    await setParticipation(props.event.id, participating.value);
    notify.info(
      participating.value
        ? `Votre participation au ${props.event.title} est notée.`
        : 'Participation retirée.'
    );
  } catch (error) {
    participating.value = !participating.value;
    notify.error(extractApiError(error, 'Votre participation n’a pas pu être enregistrée.'));
  }
}
</script>

<template>
  <q-card flat class="rimef-card featured-event" tag="article" aria-labelledby="featured-title">
    <p class="eyebrow featured-event__eyebrow">
      <q-icon name="calendar-event" size="14px" />
      Événement à la une
    </p>

    <div class="featured-event__body">
      <router-link
        :to="{ name: 'agenda' }"
        class="featured-event__visual"
        tabindex="-1"
        aria-hidden="true"
      >
        <q-icon name="calendar-event" size="48px" />
      </router-link>

      <div class="featured-event__content">
        <h2 id="featured-title" class="featured-event__title">
          <router-link :to="{ name: 'agenda' }">{{ event.title }}</router-link>
        </h2>
        <div class="featured-event__meta">
          <span class="meta"><q-icon name="calendar" size="15px" />{{ event.dates }}</span>
          <span v-if="event.place" class="meta">
            <q-icon name="map-pin" size="15px" />{{ event.place }}
          </span>
        </div>
        <p v-if="event.description" class="featured-event__description">
          {{ event.description }}
        </p>

        <div class="featured-event__who">
          <div class="featured-event__stack">
            <MemberAvatar v-if="participating" name="Vous" />
            <MemberAvatar
              v-for="person in others"
              :key="person.id"
              :name="person.name"
              :photo="person.photo"
            />
            <span v-if="hiddenCount > 0" class="featured-event__more" aria-hidden="true">
              +{{ hiddenCount }}
            </span>
          </div>
          <p>
            <b>{{ mediatorCount(count) }}</b> du réseau seront présentes.
          </p>
        </div>

        <div class="featured-event__actions">
          <AppButton label="Voir l’événement" :to="{ name: 'agenda' }" />
          <AppButton
            variant="outline"
            :icon="participating ? 'check' : undefined"
            :label="participating ? 'Je participe' : 'J’y participe'"
            :aria-pressed="participating"
            class="featured-event__join"
            :class="{ 'featured-event__join--on': participating }"
            @click="toggleParticipation"
          />
        </div>
      </div>
    </div>
  </q-card>
</template>

<style lang="scss" scoped>
.featured-event {
  padding: 18px 24px 24px;

  &__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    .q-icon {
      color: var(--petrol);
    }
  }

  &__body {
    display: grid;
    grid-template-columns: 184px minmax(0, 1fr);
    gap: 32px;
    margin-top: 16px;
  }

  // Visuel neutre en attendant les photos des événements
  &__visual {
    display: grid;
    place-items: center;
    aspect-ratio: 184 / 276;
    border-radius: var(--radius-xs);
    background: var(--petrol-light);
    color: var(--petrol);
  }

  &__content {
    display: flex;
    flex-direction: column;
    padding-top: 6px;
  }

  &__title {
    font-size: var(--fs-h2);

    a {
      color: inherit;
      text-decoration: none;

      &:hover,
      &:focus-visible {
        color: var(--link-hover);
      }
    }
  }

  &__meta {
    display: grid;
    gap: 6px;
    margin-top: 14px;
  }

  &__description {
    max-width: 46ch;
    margin-top: 12px;
    font-size: var(--fs-sm);
    line-height: 1.65;
    color: var(--ink-2);
  }

  &__who {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 22px;
    font-size: var(--fs-sm);
    color: var(--ink-2);

    b {
      font-weight: 600;
      color: var(--ink);
    }
  }

  // Pile d'avatars chevauchés (fiche Avatar)
  &__stack {
    display: flex;
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
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--ivory);
    font-size: var(--fs-xs);
    font-weight: 600;
    color: var(--ink-2);
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: auto;
    padding-top: 22px;

    .q-btn {
      flex: 1 1 170px;
      max-width: 220px;
    }
  }

  &__join--on {
    background: var(--sage-light) !important;
    color: var(--sage-ink) !important;
  }
}

@media (max-width: 719px) {
  .featured-event {
    padding: 16px;

    &__body {
      grid-template-columns: 1fr;
      gap: 18px;
      margin-top: 12px;
    }

    &__visual {
      aspect-ratio: 16 / 7;
    }

    &__actions .q-btn {
      max-width: none;
    }
  }
}
</style>
