<script setup lang="ts">
// « Autour de cette ressource » (maquette .links3) : la chaîne ressource → médiatrices →
// événement, chaque maillon avec une phrase qui explique le lien ; un maillon descend vers sa
// section de la fiche. Aplat sable, pointillés terracotta entre les maillons.
import { computed } from 'vue';

import DateTile from '@/components/DateTile.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { dayAndMonth, formatDateRange } from '@/lib/dates';
import type { ResourceDetail, ResourceMediator } from '../services/resources';

const props = defineProps<{ resource: ResourceDetail }>();
const emit = defineEmits<{ go: [section: 'resume' | 'mediatrices' | 'evenement'] }>();

/** Article et genre de chaque type : « un rapport », « une analyse »… */
const NOUNS: Record<string, { one: string; this: string; feminine: boolean }> = {
  report: { one: 'Un rapport', this: 'Ce rapport', feminine: false },
  analysis: { one: 'Une analyse', this: 'Cette analyse', feminine: true },
  article: { one: 'Un article', this: 'Cet article', feminine: false },
  podcast: { one: 'Un podcast', this: 'Ce podcast', feminine: false },
  video: { one: 'Une vidéo', this: 'Cette vidéo', feminine: true },
  tool: { one: 'Un outil', this: 'Cet outil', feminine: false },
};

const noun = computed(
  () =>
    NOUNS[props.resource.type.value] ?? {
      one: 'Une ressource',
      this: 'Cette ressource',
      feminine: true,
    }
);

const mediators = computed(() => props.resource.mediators);
const shown = computed(() => mediators.value.slice(0, 2));
const names = computed(() => {
  const list = shown.value.map((person) => person.name);
  const others = mediators.value.length - list.length;
  return others > 0
    ? `${list.join(', ')} et ${others} autre${others > 1 ? 's' : ''}`
    : list.join(', ');
});

/** Ce que chaque médiatrice a fait : « y a contribué », « l'a écrite »… */
function deed(person: ResourceMediator): string {
  const it = noun.value.feminine ? 'l’a écrite' : 'l’a écrit';
  switch (person.role?.value) {
    case 'author':
      return it;
    case 'contributor':
      return 'y a contribué';
    case 'mentioned':
      return 'y est citée';
    default:
      return noun.value.feminine ? 'la recommande au réseau' : 'le recommande au réseau';
  }
}

const event = computed(() => props.resource.event);
const tile = computed(() => {
  if (!event.value) return null;
  const { day, month } = dayAndMonth(event.value.starts_at);
  return { day: day.padStart(2, '0'), month };
});
const eventFacts = computed(() =>
  event.value
    ? [formatDateRange(event.value.starts_at, event.value.ends_at), event.value.place]
        .filter(Boolean)
        .join(' · ')
    : ''
);
</script>

<template>
  <section class="resource-links" aria-labelledby="resource-links-title">
    <p class="eyebrow">Autour de cette ressource</p>
    <h2 id="resource-links-title">
      {{ noun.one
      }}<template v-if="mediators.length && event"
        >, <em>des médiatrices</em>, une rencontre</template
      ><template v-else-if="mediators.length"> et <em>des médiatrices</em></template
      ><template v-else> et <em>une rencontre</em></template>
    </h2>

    <ol class="resource-links__chain">
      <li class="resource-links__node">
        <q-btn
          flat
          no-caps
          :ripple="false"
          class="resource-links__box"
          @click="emit('go', 'resume')"
        >
          <span class="resource-links__mini-cover" aria-hidden="true" />
          <span>
            <b>{{ noun.this }}</b>
            <small
              >{{ resource.source ?? resource.author?.name }} ·
              {{ resource.published_on.slice(0, 4) }}</small
            >
          </span>
        </q-btn>
      </li>

      <li v-if="mediators.length" class="resource-links__node">
        <q-btn
          flat
          no-caps
          :ripple="false"
          class="resource-links__box"
          @click="emit('go', 'mediatrices')"
        >
          <span class="resource-links__stack" aria-hidden="true">
            <MemberAvatar
              v-for="person in shown"
              :key="person.id"
              :name="person.name"
              :photo="person.photo_url"
              :size="34"
            />
          </span>
          <span>
            <b>{{ names }}</b>
            <small>Médiatrices RIMeF</small>
          </span>
        </q-btn>
        <p class="resource-links__rel">
          <template v-for="(person, index) in shown" :key="person.id">
            <template v-if="index > 0">, </template>
            <strong>{{ person.first_name }}</strong> {{ deed(person) }}</template
          >.
        </p>
      </li>

      <li v-if="event && tile" class="resource-links__node">
        <q-btn
          flat
          no-caps
          :ripple="false"
          class="resource-links__box"
          @click="emit('go', 'evenement')"
        >
          <DateTile :day="tile.day" :month="tile.month" class="resource-links__date" />
          <span>
            <b>{{ event.title }}</b>
            <small>{{ eventFacts }}</small>
          </span>
        </q-btn>
        <p v-if="event.note" class="resource-links__rel">{{ event.note }}</p>
      </li>
    </ol>
  </section>
</template>

<style lang="scss" scoped>
.resource-links {
  position: relative;
  overflow: hidden;
  padding: 26px 32px 28px;
  border-radius: var(--radius-sm);
  background: var(--sand);
  color: var(--ink);

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
    font-size: 1.85rem;
    line-height: 1.1;
    color: var(--petrol-dark);

    em {
      font-style: italic;
      // Terracotta foncé : le terracotta de la maquette n'atteint pas le contraste AA sur sable
      color: var(--terracotta-ink);
    }
  }

  &__chain {
    display: grid;
    grid-auto-columns: minmax(0, 1fr);
    grid-auto-flow: column;
    margin: 24px 0 0;
    padding: 0;
    list-style: none;
  }

  &__node {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;

    // Pointillés et pastille terracotta entre deux maillons
    & + & {
      padding-left: 40px;

      &::before {
        content: '';
        position: absolute;
        top: 27px;
        left: 0;
        width: 28px;
        height: 1px;
        background: repeating-linear-gradient(90deg, var(--terracotta) 0 4px, transparent 4px 8px);
        opacity: 0.6;
      }

      &::after {
        content: '';
        position: absolute;
        top: 24px;
        left: 22px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--terracotta);
      }
    }
  }

  &__box.q-btn {
    justify-content: flex-start;
    min-height: 56px;
    padding: 8px 14px 8px 8px;
    border: 1px solid var(--sand-line);
    border-radius: var(--radius-sm);
    background: #fffdf9;
    color: var(--ink);
    text-align: left;

    :deep(.q-focus-helper) {
      display: none;
    }

    :deep(.q-btn__content) {
      flex-wrap: nowrap;
      justify-content: flex-start;
      gap: 12px;
      min-width: 0;
    }

    &:hover {
      border-color: #d8ccb6;
      background: var(--paper);
    }

    b {
      display: block;
      font-size: var(--fs-sm);
      font-weight: 500;
      line-height: 1.3;
    }

    small {
      display: block;
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }

  &__mini-cover {
    position: relative;
    flex: none;
    width: 30px;
    height: 40px;
    overflow: hidden;
    border-radius: 2px;
    background: var(--petrol);
    box-shadow: inset 3px 0 0 rgba(0, 0, 0, 0.2);

    &::after {
      content: '';
      position: absolute;
      right: -6px;
      bottom: -4px;
      width: 22px;
      height: 9px;
      border-radius: 50%;
      background: var(--terracotta);
      transform: rotate(-35deg);
    }
  }

  &__stack {
    display: flex;
    flex: none;

    > * + * {
      margin-left: -8px;
    }

    :deep(.member-avatar) {
      box-shadow: 0 0 0 2px #fffdf9;
    }
  }

  &__date {
    flex: none;
    width: 40px;
    height: 44px;
  }

  &__rel {
    font-size: var(--fs-xs);
    line-height: 1.5;
    color: var(--ink-2);

    strong {
      font-weight: 600;
      color: var(--ink);
    }
  }
}

// Tablette et téléphone : maillons empilés, reliés par des pointillés verticaux
@media (max-width: 1079px) {
  .resource-links__chain {
    grid-auto-flow: row;
    gap: 0;
  }

  // Pointillés verticaux et pastille entre deux maillons empilés (maquette)
  .resource-links__node + .resource-links__node {
    padding-top: 30px;
    padding-left: 0;

    &::before {
      top: 3px;
      left: 24px;
      width: 1px;
      height: 24px;
      background: repeating-linear-gradient(180deg, var(--terracotta) 0 4px, transparent 4px 8px);
    }

    &::after {
      top: 12px;
      left: 21px;
    }
  }
}

@media (max-width: 719px) {
  .resource-links {
    padding: 22px 18px 24px;

    h2 {
      font-size: 1.5rem;
    }
  }
}
</style>
