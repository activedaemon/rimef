<script setup lang="ts">
// Médiatrice présente à un événement (maquette .pp) : portrait, nom (lien vers sa fiche), pays,
// rôle (Intervenante en terracotta, Participante en sauge), interventions (d'après le programme),
// jours de présence (événement de plusieurs jours : eventDays) et note ; expertises en plus
// dans l'onglet « Médiatrices présentes » (full), et sa disponibilité pour se retrouver sur
// place. L'enveloppe (sauf pour soi-même) demande la fenêtre de message, sans quitter la page.
import { computed } from 'vue';

import MemberAvatar from '@/components/MemberAvatar.vue';
import { useSession } from '@/stores/session';
import { presenceLabel, talkLabel, type EventAttendee } from '../services/events';
import AppTooltip from '@/components/AppTooltip.vue';

const props = withDefaults(
  defineProps<{ attendee: EventAttendee; eventDays: string[]; full?: boolean }>(),
  { full: false }
);
const emit = defineEmits<{ write: [attendee: EventAttendee] }>();

const session = useSession();
const isSelf = computed(() => props.attendee.id === session.user?.id);
const profile = computed(() => ({ name: 'member', params: { slug: props.attendee.slug } }));
const isSpeaker = computed(() => props.attendee.role.value === 'speaker');
const presence = computed(() => presenceLabel(props.attendee.attendance_days, props.eventDays));
</script>

<template>
  <article class="attendee-card">
    <div class="attendee-card__top">
      <MemberAvatar :name="attendee.name" :photo="attendee.photo_url" :size="52" />
      <div class="attendee-card__identity">
        <h3>
          <router-link :to="profile">{{ attendee.name }}</router-link>
        </h3>
        <p v-if="attendee.country" class="attendee-card__where">{{ attendee.country }}</p>
      </div>
    </div>

    <p class="attendee-card__line">
      <q-badge
        class="rimef-badge"
        :class="isSpeaker ? 'attendee-card__role--speaker' : 'attendee-card__role'"
      >
        {{ attendee.role.label }}
      </q-badge>
    </p>
    <p v-for="talk in attendee.talks" :key="talk.starts_at" class="attendee-card__line">
      {{ talkLabel(talk) }}
    </p>
    <p v-if="presence" class="attendee-card__line">{{ presence }}</p>
    <p v-if="attendee.attendance_note" class="attendee-card__line attendee-card__note">
      « {{ attendee.attendance_note }} »
    </p>
    <p v-if="attendee.wants_to_meet" class="attendee-card__line attendee-card__meet">
      <q-icon name="hand-stop" size="14px" aria-hidden="true" />Disponible pour se retrouver
    </p>
    <p v-if="full && attendee.expertises.length" class="attendee-card__tags">
      <span v-for="expertise in attendee.expertises.slice(0, 3)" :key="expertise">
        {{ expertise }}
      </span>
    </p>

    <div class="attendee-card__foot">
      <router-link :to="profile" class="link-more">Voir le profil</router-link>
      <q-btn
        v-if="!isSelf"
        flat
        round
        dense
        :aria-label="`Écrire à ${attendee.name}`"
        aria-haspopup="dialog"
        class="attendee-card__write"
        @click="emit('write', attendee)"
      >
        <AppTooltip>Écrire à {{ attendee.name }}</AppTooltip>
        <span class="attendee-card__envelope" aria-hidden="true">
          <q-icon name="mail" size="14px" />
        </span>
      </q-btn>
    </div>
  </article>
</template>

<style lang="scss" scoped>
.attendee-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 100%;
  padding: 16px;
  border-radius: var(--radius-sm);
  background: var(--card);
  color: var(--ink);

  &__top {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
  }

  &__identity {
    min-width: 0;
  }

  h3 {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.2rem;
    line-height: 1.1;

    // Lien sans le style du navigateur (maquette : a { color: inherit }), pétrole au survol
    a {
      color: inherit;
      text-decoration: none;

      &:hover,
      &:focus-visible {
        color: var(--petrol);
      }
    }
  }

  &__where {
    margin-top: 2px;
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &__line {
    font-size: var(--fs-xs);
    line-height: 1.45;
    color: var(--ink-2);
  }

  &__role.q-badge {
    background: var(--sage-light);
    color: var(--sage-ink);
  }

  &__role--speaker.q-badge {
    background: var(--terracotta-light);
    color: var(--terracotta-ink);
  }

  &__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;

    span {
      display: inline-flex;
      align-items: center;
      min-height: 22px;
      padding: 2px 8px;
      border-radius: var(--radius-xs);
      background: var(--ivory);
      font-size: 11.5px;
      color: var(--ink-2);
    }
  }

  &__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid var(--border);
  }

  &__note {
    font-style: italic;
  }

  &__meet {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--sage-ink);
  }

  // Cercle de 30 px de la maquette (.pp-msg) dans une zone tactile de 44 px, invisible
  &__write.q-btn {
    width: 44px;
    height: 44px;
    margin: -7px;
    color: var(--petrol);

    :deep(.q-focus-helper) {
      display: none;
    }

    &:hover {
      background: transparent;
    }

    &:hover .attendee-card__envelope {
      border-color: transparent;
      background: var(--petrol-light);
    }
  }

  &__envelope {
    display: grid;
    place-items: center;
    width: 30px;
    height: 30px;
    border: 1px solid var(--border);
    border-radius: 50%;
  }
}
</style>
