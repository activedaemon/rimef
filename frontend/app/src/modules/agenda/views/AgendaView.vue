<script setup lang="ts">
// Agenda (maquette Agenda) : onglets À venir / Passés / Mes événements, filtres, filtres actifs,
// barre de résultats et tri, événements groupés par mois, pagination ; à côté, mini-calendrier
// et citation (au-dessus de la liste sur tablette, en dessous sur téléphone). La recherche par texte
// passe par le champ du bandeau (meta.searchInPage) qui écrit ?q=… dans l'URL de la page.
import { type ComponentPublicInstance, computed, onMounted, ref } from 'vue';

import AppButton from '@/components/AppButton.vue';
import AppPagination from '@/components/AppPagination.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterMenu from '@/components/FilterMenu.vue';
import FilterTag from '@/components/FilterTag.vue';
import PageIntro from '@/components/PageIntro.vue';
import QuoteBlock from '@/components/QuoteBlock.vue';
import type { FilterOption } from '@/lib/filters';
import { eventNoun } from '@/lib/wording';
import AgendaCalendar from '../components/AgendaCalendar.vue';
import EventRow from '../components/EventRow.vue';
import ParticipationDialog from '../components/ParticipationDialog.vue';
import { useAgenda } from '../composables/useAgenda';
import {
  FILTER_KEYS,
  FILTER_LABELS,
  PERIOD_LABELS,
  PERIODS,
  PER_PAGE_OPTIONS,
  TOGGLE_KEYS,
  TOGGLES,
  groupByMonth,
  monthLabel,
  type AgendaEvent,
  type FilterKey,
  type Period,
  type SortKey,
  type ToggleKey,
} from '../services/events';

const agenda = useAgenda();

/** Événement dont la fenêtre « Ma participation » est ouverte. */
const participationEvent = ref<AgendaEvent | null>(null);
const participationOpen = ref(false);

function participate(event: AgendaEvent): void {
  participationEvent.value = event;
  participationOpen.value = true;
}
const { query, filters } = agenda;

function setSelection(key: FilterKey, values: string[]): void {
  agenda.update({ selected: { ...query.value.selected, [key]: values } });
}

function removeFilter(key: FilterKey, value: string): void {
  setSelection(
    key,
    query.value.selected[key].filter((item) => item !== value)
  );
}

// Filtre Date : un seul mois, « Toutes les dates » par défaut
const MONTH_OPTIONS = computed<FilterOption[]>(() => [
  { value: '', label: 'Toutes les dates' },
  ...(filters.value?.month ?? []),
]);
const month = computed({
  get: () => [query.value.month],
  set: (values: string[]) => agenda.setMonth(values[0] ?? ''),
});

// « Plus de filtres » : critères oui / non
// Zones et thématiques : seulement si le profil les renseigne (ou si le critère est déjà coché)
const moreOptions = computed<FilterOption[]>(() =>
  TOGGLE_KEYS.filter(
    (key) =>
      (key !== 'my_zones' && key !== 'my_themes') ||
      filters.value?.toggles[key] ||
      query.value.toggles[key]
  ).map((key) => ({ value: key, label: TOGGLES[key].label, hint: TOGGLES[key].hint }))
);
const more = computed({
  get: () => TOGGLE_KEYS.filter((key) => query.value.toggles[key]),
  set: (values: string[]) => setToggles(values),
});

function setToggles(values: string[]): void {
  agenda.update({
    toggles: Object.fromEntries(TOGGLE_KEYS.map((key) => [key, values.includes(key)])) as Record<
      ToggleKey,
      boolean
    >,
  });
}

const activeToggles = computed(() => TOGGLE_KEYS.filter((key) => query.value.toggles[key]));

const period = computed({
  get: () => query.value.period,
  set: (value: Period) => agenda.setPeriod(value),
});

const sortOptions = computed<{ value: SortKey; label: string }[]>(() => [
  { value: 'date', label: query.value.period === 'past' ? 'Date (plus récents)' : 'Date' },
  { value: 'attendees', label: 'Médiatrices présentes' },
]);
const sort = computed({
  get: () => query.value.sort,
  set: (value: SortKey) => agenda.update({ sort: value }),
});

// Nombre d'événements de l'onglet, sans filtre (« 24 événements à venir »)
const periodTotal = computed(
  () => filters.value?.periods[query.value.period] ?? agenda.total.value
);
const PERIOD_SUFFIX: Record<Period, string> = {
  upcoming: 'à venir',
  past: 'passés',
  mine: 'dans votre agenda',
};

// Intertitres par mois seulement avec le tri par date
const groups = computed(() =>
  query.value.sort === 'date'
    ? groupByMonth(agenda.events.value)
    : [{ key: '', events: agenda.events.value }]
);

const PER_PAGE_CHOICES = PER_PAGE_OPTIONS.map((value) => ({
  label: `${value} par page`,
  value,
}));

// Changement de page : retour en douceur en haut des résultats
const resultsBar = ref<ComponentPublicInstance | HTMLElement | null>(null);

function changePage(page: number): void {
  agenda.goToPage(page);
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const element =
    resultsBar.value instanceof HTMLElement ? resultsBar.value : resultsBar.value?.$el;
  (element as HTMLElement | undefined)?.scrollIntoView({
    behavior: reduceMotion ? 'auto' : 'smooth',
    block: 'start',
  });
}

onMounted(() => void agenda.start());
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro title="Agenda" lead="Les rencontres, forums et formations du réseau." leaf />

      <div class="agenda-layout">
        <div class="agenda-main">
          <div class="agenda-tabs">
            <q-tabs
              v-model="period"
              no-caps
              inline-label
              align="left"
              :breakpoint="0"
              active-color="primary"
              indicator-color="primary"
              class="agenda-tabs__list"
              aria-label="Période"
            >
              <q-tab v-for="key in PERIODS" :key="key" :name="key" :ripple="false">
                <span>
                  {{ PERIOD_LABELS[key] }}
                  <span v-if="filters" class="agenda-tabs__count"
                    >({{ filters.periods[key] }})</span
                  >
                </span>
              </q-tab>
            </q-tabs>
          </div>

          <div class="agenda-filters" aria-label="Filtres" role="group">
            <FilterMenu
              v-for="key in FILTER_KEYS"
              :key="key"
              :label="FILTER_LABELS[key]"
              :options="filters?.[key] ?? []"
              :model-value="query.selected[key]"
              @update:model-value="(values: string[]) => setSelection(key, values)"
            />
            <FilterMenu
              v-model="month"
              label="Date"
              single
              :value-label="agenda.activeMonth.value"
              :options="MONTH_OPTIONS"
            />
            <FilterMenu
              v-model="more"
              label="Plus de filtres"
              icon="adjustments-horizontal"
              ghost
              :options="moreOptions"
            />
          </div>

          <div v-if="agenda.filtered.value" class="agenda-active" aria-live="polite">
            <FilterTag
              v-if="query.q"
              :label="`« ${query.q} »`"
              :aria-label="`Retirer la recherche ${query.q}`"
              @click="agenda.update({ q: '' })"
            />
            <FilterTag
              v-for="filter in agenda.activeFilters.value"
              :key="`${filter.key}-${filter.value}`"
              :label="filter.label"
              :aria-label="`Retirer le filtre ${filter.label}`"
              @click="removeFilter(filter.key, filter.value)"
            />
            <FilterTag
              v-if="query.month"
              :label="monthLabel(query.month)"
              :aria-label="`Retirer le filtre ${monthLabel(query.month)}`"
              @click="agenda.setMonth('')"
            />
            <FilterTag
              v-if="query.day"
              :label="agenda.activeDay.value"
              :aria-label="`Retirer le filtre ${agenda.activeDay.value}`"
              @click="agenda.setDay('')"
            />
            <FilterTag
              v-for="key in activeToggles"
              :key="key"
              :label="TOGGLES[key].label"
              :aria-label="`Retirer le filtre ${TOGGLES[key].label}`"
              @click="setToggles(more.filter((value) => value !== key))"
            />
          </div>

          <div ref="resultsBar" class="agenda-bar">
            <p class="agenda-bar__count" aria-live="polite">
              <template v-if="agenda.filtered.value">
                <b>{{ agenda.total.value }}</b> {{ eventNoun(agenda.total.value) }} sur
                {{ periodTotal }}
                <q-btn
                  flat
                  dense
                  no-caps
                  class="link-more agenda-bar__reset"
                  label="Tout effacer"
                  @click="agenda.reset"
                />
              </template>
              <template v-else>
                <b>{{ periodTotal }}</b> {{ eventNoun(periodTotal) }}
                {{ PERIOD_SUFFIX[query.period] }}
              </template>
            </p>

            <q-select
              v-model="sort"
              :options="sortOptions"
              emit-value
              map-options
              dense
              outlined
              options-dense
              class="rimef-select agenda-bar__sort"
              aria-label="Trier par"
            >
              <template #prepend>
                <span class="agenda-bar__sort-label gt-xs">Trier par :</span>
              </template>
            </q-select>
          </div>

          <div v-if="!agenda.loaded.value" class="agenda-loading">
            <q-spinner color="primary" size="32px" />
          </div>

          <EmptyState
            v-else-if="!agenda.events.value.length"
            icon="calendar"
            title="Aucun événement ne correspond"
            text="Essayez un autre mot-clé, une autre date ou retirez un filtre."
            class="agenda-empty"
          >
            <AppButton
              v-if="agenda.filtered.value"
              variant="outline"
              label="Réinitialiser les filtres"
              @click="agenda.reset"
            />
          </EmptyState>

          <template v-else>
            <div class="agenda-list" :class="{ 'agenda-list--busy': agenda.loading.value }">
              <section
                v-for="group in groups"
                :key="group.key || 'all'"
                :aria-labelledby="group.key ? `mois-${group.key}` : undefined"
              >
                <h2 v-if="group.key" :id="`mois-${group.key}`" class="agenda-month">
                  {{ monthLabel(group.key) }}
                  <small>{{ group.events.length }} {{ eventNoun(group.events.length) }}</small>
                </h2>
                <ol class="agenda-events">
                  <li v-for="event in group.events" :key="event.id">
                    <EventRow :event="event" @toggle-participation="participate(event)" />
                  </li>
                </ol>
              </section>
            </div>

            <nav
              v-if="agenda.total.value > PER_PAGE_OPTIONS[0]"
              class="agenda-pager"
              aria-label="Pagination des événements"
            >
              <p class="agenda-pager__range" aria-live="polite">
                Événements <b>{{ agenda.from.value }}–{{ agenda.to.value }}</b> sur
                {{ agenda.total.value }}
              </p>
              <q-select
                :model-value="query.perPage"
                :options="PER_PAGE_CHOICES"
                emit-value
                map-options
                dense
                outlined
                options-dense
                class="rimef-select agenda-pager__size"
                aria-label="Nombre d’événements par page"
                @update:model-value="agenda.setPerPage"
              />
              <AppPagination
                v-if="agenda.lastPage.value > 1"
                :model-value="agenda.page.value"
                :max="agenda.lastPage.value"
                class="agenda-pager__pages"
                @update:model-value="changePage"
              />
            </nav>
          </template>
        </div>

        <aside class="agenda-side" aria-label="Calendrier">
          <AgendaCalendar
            :model-value="query.day"
            :focus-month="query.month || query.day.slice(0, 7)"
            @update:model-value="agenda.setDay"
          />
          <QuoteBlock
            variant="card"
            text="Construire, rencontre après rencontre, une communauté de médiatrices francophones."
            class="agenda-quote"
          />
        </aside>
      </div>
    </div>

    <ParticipationDialog
      v-if="participationEvent"
      v-model="participationOpen"
      :event="participationEvent"
      :participating="participationEvent.is_participating"
      @saved="agenda.onParticipationSaved(participationEvent, $event)"
    />
  </q-page>
</template>

<style lang="scss" scoped>
// Liste et colonne de droite (calendrier, citation) fixée au défilement
.agenda-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 330px;
  gap: 32px;
  align-items: start;
}

.agenda-main {
  min-width: 0;
}

.agenda-side {
  position: sticky;
  top: calc(var(--header-h) + 20px + env(safe-area-inset-top, 0px));
  display: grid;
  gap: 20px;
  margin-top: 6px;
}

// Citation plus longue que sur l'accueil : police réduite, toute la largeur de la carte
.agenda-side .agenda-quote :deep(.quote-block__text) {
  max-width: none;
  font-size: clamp(1.25rem, 1.1rem + 0.5vw, 1.5rem);
}

// Onglets (maquette .tabs) : soulignement pétrole, compteur atténué
.agenda-tabs {
  border-bottom: 1px solid var(--border);

  &__list {
    :deep(.q-tab) {
      min-height: 50px;
      padding: 0 2px;
      margin-right: 36px;
      font-size: var(--fs-sm);
      font-weight: 500;
      color: var(--ink-2);
    }

    :deep(.q-tab--active) {
      font-weight: 600;
      color: var(--petrol);
    }

    :deep(.q-tab__indicator) {
      height: 2px;
    }

    :deep(.q-focus-helper) {
      display: none;
    }
  }

  &__count {
    margin-left: 2px;
    font-size: 11.5px;
    font-weight: 400;
    color: var(--muted);
    font-variant-numeric: tabular-nums;

    .q-tab--active & {
      color: var(--petrol);
    }
  }
}

.agenda-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 18px;
}

.agenda-active {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.agenda-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-top: 18px;
  // Arrivée par le changement de page : la barre ne passe pas sous l'en-tête fixe
  scroll-margin-top: calc(var(--header-h) + 16px);

  &__count {
    font-size: var(--fs-sm);
    color: var(--ink-2);

    b {
      font-weight: 600;
      color: var(--petrol);
      font-variant-numeric: tabular-nums;
    }
  }

  &__reset {
    margin-left: 10px;
  }

  &__sort {
    min-width: 170px;
  }

  &__sort-label {
    font-size: var(--fs-xs);
    color: var(--ink-2);
  }
}

.agenda-loading {
  display: grid;
  place-items: center;
  padding-block: var(--s-16);
}

.agenda-empty {
  margin-top: 14px;
}

.agenda-list {
  margin-top: 14px;
  transition: opacity 0.15s;

  // Nouvelle recherche en cours : la liste précédente reste visible, atténuée
  &--busy {
    opacity: 0.55;
  }
}

// Intertitre de mois (maquette .ag-month) : serif, nombre d'événements, filet jusqu'au bord
.agenda-month {
  display: flex;
  align-items: baseline;
  gap: 12px;
  padding: 22px 0 10px;
  // noinspection CssNoGenericFontName
  font-family: var(--font-serif);
  font-size: 1.35rem;
  font-weight: 400;
  color: var(--ink);

  section:first-child & {
    padding-top: 6px;
  }

  small {
    font-family: var(--font-ui);
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &::after {
    content: '';
    flex: 1;
    align-self: center;
    height: 1px;
    background: var(--border);
  }
}

.agenda-events {
  display: grid;
  gap: 10px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.agenda-pager {
  // Plage à gauche, taille de page au milieu, pages à droite (comme au Réseau)
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 16px;
  margin-top: 36px;
  padding-top: 20px;
  border-top: 1px solid var(--border);

  &__range {
    font-size: var(--fs-sm);
    color: var(--muted);
    font-variant-numeric: tabular-nums;

    b {
      font-weight: 600;
      color: var(--ink);
    }
  }

  &__size {
    min-width: 140px;
  }

  &__pages {
    justify-self: end;
  }
}

// Tablette : une colonne, calendrier et citation côte à côte au-dessus de la liste
@media (max-width: 1079px) {
  .agenda-layout {
    grid-template-columns: 1fr;
    gap: 28px;
  }

  .agenda-side {
    position: static;
    order: -1;
    grid-template-columns: 1fr 1fr;
    align-items: stretch;
    margin-top: 0;
  }
}

// Téléphone : onglets fixés sous l'en-tête, pagination empilée (les filtres passent à la ligne)
@media (max-width: 719px) {
  .agenda-tabs {
    position: sticky;
    top: calc(var(--header-h) + env(safe-area-inset-top, 0px));
    z-index: 20;
    margin-inline: calc(var(--gutter) * -1);
    padding-inline: var(--gutter);
    background: var(--ivory);

    &__list :deep(.q-tab) {
      margin-right: 26px;
    }
  }

  .agenda-bar__sort {
    min-width: 0;
  }

  // Calendrier et citation sous la liste
  .agenda-side {
    order: 0;
    grid-template-columns: 1fr;
  }

  .agenda-pager {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 28px;
    padding-top: 16px;
  }
}
</style>
