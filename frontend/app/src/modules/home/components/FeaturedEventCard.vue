<script setup lang="ts">
// Événement à la une (maquette Accueil) : photo de l'événement (visuel neutre sans photo), titre, dates, lieu, participantes,
// actions. « J'y participe » ouvre la fenêtre « Ma participation » (jours, note).
import { computed, ref } from 'vue';

import AppButton from '@/components/AppButton.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { mediatorCount } from '@/lib/wording';
import { useSession } from '@/stores/session';
import ParticipationDialog from '@modules/agenda/components/ParticipationDialog.vue';
import type { FeaturedEvent } from '../services/home-feed';

const props = defineProps<{ event: FeaturedEvent }>();

/** Avatars affichés avant le « +N ». */
const VISIBLE_ATTENDEES = 4;

const session = useSession();
const participating = ref(props.event.isParticipating);
const photoFailed = ref(false);
const eventLink = computed(() => ({ name: 'event', params: { slug: props.event.slug } }));

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

/** Fenêtre « Ma participation » : la carte suit la participation enregistrée. */
const participationOpen = ref(false);
const dialogEvent = computed(() => ({
  id: props.event.id,
  slug: props.event.slug,
  title: props.event.title,
  starts_at: props.event.startsAt,
  ends_at: props.event.endsAt,
}));
</script>

<template>
  <q-card flat class="rimef-card featured-event" tag="article" aria-labelledby="featured-title">
    <p class="eyebrow featured-event__eyebrow">
      <q-icon name="calendar-event" size="14px" />
      Événement à la une
    </p>

    <div class="featured-event__body">
      <router-link :to="eventLink" class="featured-event__visual" tabindex="-1" aria-hidden="true">
        <img
          v-if="event.photo && !photoFailed"
          :src="event.photo"
          alt=""
          width="184"
          height="276"
          decoding="async"
          @error="photoFailed = true"
        />
        <q-icon v-else name="calendar-event" size="48px" />
      </router-link>

      <div class="featured-event__content">
        <h2 id="featured-title" class="featured-event__title">
          <router-link :to="eventLink">{{ event.title }}</router-link>
        </h2>
        <div class="featured-event__meta">
          <span class="meta"><q-icon name="calendar" size="15px" />{{ event.dates }}</span>
          <span v-if="event.place" class="meta">
            <q-icon name="map-pin" size="15px" />{{ event.place }}
          </span>
        </div>
        <p v-if="event.summary" class="featured-event__summary">
          {{ event.summary }}
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
          <AppButton label="Voir l’événement" :to="eventLink" />
          <AppButton
            variant="outline"
            :icon="participating ? 'check' : 'calendar-plus'"
            :label="participating ? 'Je participe' : 'J’y participe'"
            :aria-pressed="participating"
            class="featured-event__join"
            :class="{ 'featured-event__join--on': participating }"
            aria-haspopup="dialog"
            @click="participationOpen = true"
          />
        </div>
      </div>
    </div>

    <ParticipationDialog
      v-model="participationOpen"
      :event="dialogEvent"
      :participating="participating"
      @saved="participating = $event"
    />
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

  // Photo de l'événement, recadrée ; visuel neutre sans photo
  &__visual {
    display: grid;
    place-items: center;
    overflow: hidden;
    aspect-ratio: 184 / 276;
    border-radius: var(--radius-xs);
    background: var(--petrol-light);
    color: var(--petrol);

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
  }

  // Zoom de la photo au survol de la carte, plus marqué que sur les cartes des membres
  &:hover &__visual img {
    transform: scale(1.06);
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

  &__summary {
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
