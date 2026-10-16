<script setup lang="ts">
// Mini-calendrier de l'Agenda (maquette .cal), sur q-date : semaine du lundi (langue fr),
// jours avec un événement marqués (fond pétrole clair, point terracotta) et seuls cliquables,
// aujourd'hui cerclé. Cliquer un jour le choisit (v-model « 2026-11-12 »), le recliquer l'annule.
// En-tête et pied de la maquette : titre du mois, mois précédent / suivant, « Aujourd'hui ».
import type { QDate } from 'quasar';
import { computed, onMounted, ref, watch } from 'vue';

import { fetchCalendar, monthLabel, todayKey, type CalendarDay } from '../services/events';
import AppTooltip from '@/components/AppTooltip.vue';

const props = defineProps<{
  /** Mois à afficher quand il change (filtre Date, jour choisi) : « 2026-11 ». */
  focusMonth?: string;
}>();

const day = defineModel<string>({ required: true });

const today = todayKey();
const dateRef = ref<QDate | null>(null);
const view = ref((props.focusMonth || day.value || today).slice(0, 7));

// Jours avec événements, par mois déjà chargé (« 2026-11 » → jours)
const months = ref(new Map<string, CalendarDay[]>());
const eventDays = computed(
  () => new Set((months.value.get(view.value) ?? []).map((item) => item.date))
);

async function loadMonth(month: string): Promise<void> {
  if (months.value.has(month)) return;
  try {
    const days = await fetchCalendar(month);
    months.value = new Map(months.value).set(month, days);
  } catch {
    // Sans les jours marqués, le calendrier reste consultable ; la liste n'en dépend pas
  }
}

// q-date donne les dates au format « 2026/11/12 »
function hasEvent(date: string): boolean {
  return eventDays.value.has(date.replaceAll('/', '-'));
}

const model = computed({
  get: () => day.value || null,
  set: (value: string | null) => {
    day.value = value ?? '';
  },
});

function onNavigation({ year, month }: { year: number; month: number }): void {
  view.value = `${year}-${String(month).padStart(2, '0')}`;
  void loadMonth(view.value);
}

function showMonth(month: string): void {
  const [year, number] = month.split('-').map(Number);
  dateRef.value?.setCalendarTo(year, number);
}

watch(
  () => props.focusMonth,
  (month) => {
    if (month && month !== view.value) showMonth(month);
  }
);

onMounted(() => void loadMonth(view.value));
</script>

<template>
  <q-card
    flat
    tag="section"
    class="rimef-card agenda-calendar"
    aria-labelledby="agenda-calendar-title"
  >
    <div class="agenda-calendar__head">
      <h2 id="agenda-calendar-title" class="agenda-calendar__title" aria-live="polite">
        {{ monthLabel(view) }}
      </h2>
      <div class="agenda-calendar__nav">
        <q-btn
          flat
          round
          dense
          icon="chevron-left"
          aria-label="Mois précédent"
          @click="dateRef?.offsetCalendar('month', true)"
        >
          <AppTooltip>Mois précédent</AppTooltip>
        </q-btn>
        <q-btn
          flat
          round
          dense
          icon="chevron-right"
          aria-label="Mois suivant"
          @click="dateRef?.offsetCalendar('month')"
        >
          <AppTooltip>Mois suivant</AppTooltip>
        </q-btn>
      </div>
    </div>

    <q-date
      ref="dateRef"
      v-model="model"
      mask="YYYY-MM-DD"
      minimal
      flat
      color="primary"
      event-color="secondary"
      :events="hasEvent"
      :options="hasEvent"
      :default-year-month="view.replace('-', '/')"
      class="agenda-calendar__date"
      @navigation="onNavigation"
    />

    <div class="agenda-calendar__foot">
      <span><i aria-hidden="true" />Jour avec un événement</span>
      <q-btn flat dense no-caps class="link-more" label="Aujourd’hui" @click="showMonth(today)" />
    </div>
  </q-card>
</template>

<style lang="scss" scoped>
.agenda-calendar {
  padding: 18px 18px 16px;

  &__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
  }

  &__title {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.3rem;
    font-weight: 400;
  }

  &__nav {
    display: flex;
    gap: 2px;

    .q-btn {
      width: 36px;
      height: 36px;
      color: var(--ink-2);
    }
  }

  // q-date restylé : sans sa propre navigation ni ses voiles, jours ronds de 34 px
  &__date {
    width: 100%;
    min-width: 0;
    background: transparent;

    :deep(.q-date__navigation) {
      display: none;
    }

    :deep(.q-date__content),
    :deep(.q-date__view) {
      padding: 0;
    }

    :deep(.q-date__calendar-weekdays > div) {
      font-size: 10.5px;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--muted);
      opacity: 1;
    }

    :deep(.q-date__calendar-item) {
      height: 38px;
    }

    :deep(.q-date__calendar-item > div),
    :deep(.q-date__calendar-item .q-btn) {
      width: 34px;
      height: 34px;
      font-size: var(--fs-xs);
      font-variant-numeric: tabular-nums;
    }

    // Jours sans événement : texte simple, non cliquables (sans l'opacité de Quasar)
    :deep(.q-date__calendar-item--out) {
      opacity: 1;
      color: var(--ink-2);
    }

    // Jours avec un événement : fond pétrole clair, texte pétrole
    :deep(.q-date__calendar-item--in .q-btn:not(.bg-primary)) {
      background: var(--petrol-light);
      color: var(--petrol);
      font-weight: 600;

      &:hover {
        background: #ccdddf;
      }
    }

    :deep(.q-date__calendar-item .q-focus-helper) {
      display: none;
    }

    // Aujourd'hui : cerclé d'un filet
    :deep(.q-date__today) {
      box-shadow: inset 0 0 0 1px var(--border);
      font-weight: 600;
      color: var(--ink);
    }

    // Point de l'événement, blanc sur le jour choisi
    :deep(.q-date__event) {
      bottom: 4px;
      width: 4px;
      height: 4px;
      margin-left: -2px;
    }

    :deep(.bg-primary .q-date__event) {
      background: var(--paper) !important;
    }
  }

  &__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 8px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
    font-size: var(--fs-xs);
    color: var(--muted);

    i {
      display: inline-block;
      width: 8px;
      height: 8px;
      margin-right: 6px;
      border-radius: 50%;
      background: var(--petrol-light);
      box-shadow: inset 0 0 0 1px #bcd2d4;
    }
  }
}
</style>
