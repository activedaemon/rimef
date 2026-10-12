<script setup lang="ts">
// Bloc « Le réseau RIMeF à cet événement » (maquette .net, aplat sable de l'audit visuel) :
// nombre de médiatrices présentes, avatars, les trois premières (intervenantes d'abord), nombre
// d'intervenantes et jour de leur prise de parole (d'après le programme), « Proposer de se
// retrouver sur place » (bascule : « Disponible pour se retrouver ») et « Voir les N
// médiatrices » (onglet dédié). L'enveloppe d'une carte demande la fenêtre de message.
import { computed } from 'vue';

import AppButton from '@/components/AppButton.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { LEAF_PATH, leafTransform } from '@/lib/leaf';
import { mediatorNoun } from '@/lib/wording';
import { eventDays, type EventAttendee, type EventDetail } from '../services/events';
import EventAttendeeCard from './EventAttendeeCard.vue';

const props = defineProps<{ event: EventDetail }>();
const emit = defineEmits<{
  showPeople: [];
  toggleMeeting: [];
  write: [attendee: EventAttendee];
}>();

const count = computed(() => props.event.attendees.length);
const stack = computed(() => props.event.attendees.slice(0, 4));
const hidden = computed(() => count.value - stack.value.length);
const speakers = computed(
  () => props.event.attendees.filter((person) => person.role.value === 'speaker').length
);
const isPast = computed(() => props.event.status === 'past');
const days = computed(() => eventDays(props.event));

/**
 * Jour(s) où les intervenantes du réseau prennent la parole, d'après le programme :
 * « le 10 novembre », « les 10 et 11 novembre » ; rien au-delà de deux jours ou sans programme.
 */
const speakingDays = computed(() => {
  const slugs = new Set(
    props.event.attendees.filter((person) => person.role.value === 'speaker').map((p) => p.slug)
  );
  const days = props.event.program
    .filter((day) =>
      day.sessions.some((session) => session.speaker && slugs.has(session.speaker.slug))
    )
    .map((day) => {
      const [year, month, date] = day.date.split('-').map(Number);
      return new Date(year!, month! - 1, date!);
    });
  const format = (options: Intl.DateTimeFormatOptions, date: Date) =>
    new Intl.DateTimeFormat('fr-FR', options).format(date);

  if (days.length === 1) return `le ${format({ day: 'numeric', month: 'long' }, days[0]!)}`;
  if (days.length === 2) {
    const [first, second] = days as [Date, Date];
    return first.getMonth() === second.getMonth()
      ? `les ${first.getDate()} et ${format({ day: 'numeric', month: 'long' }, second)}`
      : `les ${format({ day: 'numeric', month: 'long' }, first)} et ${format({ day: 'numeric', month: 'long' }, second)}`;
  }
  return '';
});

/** Fin de la phrase après « 1 intervenante du réseau » : « prend la parole le 10 novembre. » */
const speakingText = computed(
  () =>
    [speakers.value > 1 ? 'prennent' : 'prend', 'la parole', speakingDays.value]
      .filter(Boolean)
      .join(' ') + '.'
);
</script>

<template>
  <section class="event-network" aria-labelledby="network-title">
    <svg class="event-network__leaf" viewBox="0 0 150 110" aria-hidden="true">
      <path :d="LEAF_PATH" :transform="leafTransform(140, 104, -148, 110, 38)" fill="#C86F55" />
      <path :d="LEAF_PATH" :transform="leafTransform(142, 106, -112, 80, 28)" fill="#8FA89C" />
    </svg>

    <div class="event-network__head">
      <div>
        <p class="eyebrow">Le réseau RIMeF à cet événement</p>
        <h2 id="network-title">
          <b>{{ count }}</b> {{ mediatorNoun(count) }} {{ count > 1 ? 'présentes' : 'présente' }}
        </h2>
        <p class="event-network__sub">
          {{
            isPast
              ? 'Retrouvez les médiatrices du réseau qui y étaient et poursuivez vos échanges.'
              : 'Voyez qui du réseau sera sur place, préparez vos échanges et retrouvez-vous pendant l’événement.'
          }}
        </p>
      </div>
      <span class="event-network__stack" aria-hidden="true">
        <MemberAvatar
          v-for="person in stack"
          :key="person.id"
          :name="person.name"
          :photo="person.photo_url"
          :size="44"
        />
        <span v-if="hidden > 0" class="event-network__more">+{{ hidden }}</span>
      </span>
    </div>

    <ul class="event-network__list">
      <li v-for="person in event.attendees.slice(0, 3)" :key="person.id">
        <EventAttendeeCard :attendee="person" :event-days="days" @write="emit('write', $event)" />
      </li>
    </ul>

    <div class="event-network__foot">
      <p v-if="speakers">
        <b>{{ speakers }} {{ speakers > 1 ? 'intervenantes' : 'intervenante' }}</b> du réseau
        {{ speakingText }}
      </p>
      <div class="event-network__buttons">
        <AppButton
          v-if="!isPast"
          variant="outline"
          icon="hand-stop"
          :label="
            event.wants_to_meet
              ? 'Disponible pour se retrouver'
              : 'Proposer de se retrouver sur place'
          "
          :aria-pressed="event.wants_to_meet"
          class="event-network__meet"
          @click="emit('toggleMeeting')"
        />
        <AppButton :label="`Voir les ${count} médiatrices`" @click="emit('showPeople')" />
      </div>
    </div>
  </section>
</template>

<style lang="scss" scoped>
.event-network {
  position: relative;
  overflow: hidden;
  padding: 28px 32px 26px;
  border-radius: var(--radius-sm);
  background: var(--sand);
  color: var(--ink);

  &__leaf {
    position: absolute;
    top: -8px;
    right: -10px;
    width: 150px;
    height: 110px;
    opacity: 0.9;
    pointer-events: none;
  }

  &__head {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
  }

  .eyebrow {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--ink-2);

    &::before {
      content: '';
      width: 14px;
      height: 1px;
      background: var(--terracotta);
    }
  }

  h2 {
    margin-top: 8px;
    font-size: 2rem;
    line-height: 1.08;
    color: var(--petrol-dark);

    b {
      font-weight: 400;
      font-variant-numeric: tabular-nums;
      // Terracotta foncé : le terracotta de la maquette n'atteint pas le contraste AA sur sable
      color: var(--terracotta-ink);
    }
  }

  &__sub {
    max-width: 52ch;
    margin-top: 8px;
    font-size: var(--fs-sm);
    color: var(--ink-2);
  }

  &__stack {
    display: flex;
    align-items: center;

    > * + * {
      margin-left: -8px;
    }

    :deep(.member-avatar),
    .event-network__more {
      box-shadow: 0 0 0 2px var(--sand);
    }
  }

  &__more {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border: 1px solid var(--sand-line);
    border-radius: 50%;
    background: var(--paper);
    font-size: var(--fs-xs);
    font-weight: 600;
    color: var(--ink-2);
  }

  &__list {
    position: relative;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin: 22px 0 0;
    padding: 0;
    list-style: none;
  }

  &__foot {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-top: 18px;

    p {
      font-size: var(--fs-sm);
      color: var(--ink-2);

      b {
        font-weight: 600;
        color: var(--ink);
      }
    }
  }

  &__buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  &__meet.app-btn {
    background: transparent;
  }
}

// Petite tablette : cartes en lignes
@media (max-width: 899px) {
  .event-network__list {
    grid-template-columns: 1fr;

    :deep(.attendee-card) {
      flex-direction: row;
      flex-wrap: wrap;
      align-items: center;
    }

    :deep(.attendee-card__line) {
      flex: 1;
      min-width: 160px;
    }

    :deep(.attendee-card__foot) {
      margin-top: 0;
      margin-left: auto;
      padding-top: 0;
      border-top: 0;
    }
  }
}

// Téléphone : bloc pleine largeur, boutons empilés
@media (max-width: 719px) {
  .event-network {
    margin-inline: calc(var(--gutter) * -1);
    padding: 22px 16px 18px;
    border-radius: 0;

    h2 {
      font-size: 1.65rem;
    }

    &__head &__stack {
      display: none;
    }

    &__buttons,
    &__buttons .app-btn {
      width: 100%;
    }
  }
}
</style>
