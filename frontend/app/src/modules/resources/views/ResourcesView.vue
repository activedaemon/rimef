<script setup lang="ts">
// Bibliothèque (maquette Ressources) : intro, filtres (Type, Thématique, Région, Langue, Date,
// Plus de filtres), filtres actifs, puis la barre de résultats et tri, la grille de cartes et la
// pagination, avec à droite la colonne des avis des médiatrices.
// La recherche par texte passe par le champ du bandeau (meta.searchInPage) qui écrit ?q=… dans
// l'URL de la page.
import { type ComponentPublicInstance, computed, onMounted, ref } from 'vue';

import AppButton from '@/components/AppButton.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterMenu from '@/components/FilterMenu.vue';
import ActiveFilters from '@/components/ActiveFilters.vue';
import FilterTag from '@/components/FilterTag.vue';
import ListPager from '@/components/ListPager.vue';
import PageIntro from '@/components/PageIntro.vue';
import ResultsBar from '@/components/ResultsBar.vue';
import type { FilterOption } from '@/lib/filters';
import ResourceCard from '../components/ResourceCard.vue';
import ResourceVoices from '../components/ResourceVoices.vue';
import { useResources } from '../composables/useResources';
import {
  FILTER_KEYS,
  FILTER_LABELS,
  PER_PAGE_OPTIONS,
  SORTS,
  SORT_LABELS,
  TOGGLE_KEYS,
  TOGGLES,
  fetchVoices,
  type FilterKey,
  type SortKey,
  type ResourceVoice,
  type ToggleKey,
} from '../services/resources';

const library = useResources();
const { query, filters } = library;

function setSelection(key: FilterKey, values: string[]): void {
  library.update({ selected: { ...query.value.selected, [key]: values } });
}

function removeFilter(key: FilterKey, value: string): void {
  setSelection(
    key,
    query.value.selected[key].filter((item) => item !== value)
  );
}

// Filtre Date : une seule valeur, « Toutes les dates » par défaut
const DATE_OPTIONS = computed<FilterOption[]>(() => [
  { value: '', label: 'Toutes les dates' },
  ...(filters.value?.date ?? []),
]);
const date = computed({
  get: () => [query.value.date],
  set: (values: string[]) => library.update({ date: values[0] ?? '' }),
});

// « Plus de filtres » : critères oui / non
const MORE_OPTIONS: FilterOption[] = TOGGLE_KEYS.map((key) => ({
  value: key,
  label: TOGGLES[key].label,
  hint: TOGGLES[key].hint,
}));
const more = computed({
  get: () => TOGGLE_KEYS.filter((key) => query.value.toggles[key]),
  set: (values: string[]) => setToggles(values),
});

function setToggles(values: string[]): void {
  library.update({
    toggles: Object.fromEntries(TOGGLE_KEYS.map((key) => [key, values.includes(key)])) as Record<
      ToggleKey,
      boolean
    >,
  });
}

const activeToggles = computed(() => TOGGLE_KEYS.filter((key) => query.value.toggles[key]));

const SORT_OPTIONS = SORTS.map((value) => ({ value, label: SORT_LABELS[value] }));
const sort = computed({
  get: () => query.value.sort,
  set: (value: SortKey) => library.update({ sort: value }),
});

// Taille de la bibliothèque, sans filtre (« 86 ressources »)
const libraryTotal = computed(() => filters.value?.total ?? library.total.value);

// Changement de page : retour en douceur en haut des résultats
const resultsBar = ref<ComponentPublicInstance | HTMLElement | null>(null);

function changePage(page: number): void {
  library.goToPage(page);
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const element =
    resultsBar.value instanceof HTMLElement ? resultsBar.value : resultsBar.value?.$el;
  (element as HTMLElement | undefined)?.scrollIntoView({
    behavior: reduceMotion ? 'auto' : 'smooth',
    block: 'start',
  });
}

// Avis des médiatrices : sans eux (erreur ou aucun avis), le bandeau ne s'affiche pas
const voices = ref<ResourceVoice[]>([]);

async function loadVoices(): Promise<void> {
  try {
    voices.value = await fetchVoices();
  } catch {
    voices.value = [];
  }
}

onMounted(() => {
  void library.start();
  void loadVoices();
});
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <div class="resources-layout" :class="{ 'resources-layout--with-voices': voices.length }">
        <PageIntro
          class="resources-intro"
          title="Ressources"
          lead="Rapports, analyses, podcasts, vidéos et outils sur la médiation et la paix."
          leaf
        />

        <div class="resources-main">
          <div class="resources-filters" aria-label="Filtres" role="group">
            <FilterMenu
              v-for="key in FILTER_KEYS"
              :key="key"
              :label="FILTER_LABELS[key]"
              :options="filters?.[key] ?? []"
              :model-value="query.selected[key]"
              @update:model-value="(values: string[]) => setSelection(key, values)"
            />
            <FilterMenu
              v-model="date"
              label="Date"
              single
              :value-label="library.activeDate.value"
              :options="DATE_OPTIONS"
            />
            <FilterMenu
              v-model="more"
              label="Plus de filtres"
              icon="adjustments-horizontal"
              ghost
              :options="MORE_OPTIONS"
            />
          </div>

          <ActiveFilters :filtered="library.filtered.value" @clear="library.reset">
            <FilterTag
              v-if="query.q"
              :label="`« ${query.q} »`"
              :aria-label="`Retirer la recherche ${query.q}`"
              @click="library.update({ q: '' })"
            />
            <FilterTag
              v-for="filter in library.activeFilters.value"
              :key="`${filter.key}-${filter.value}`"
              :label="filter.label"
              :aria-label="`Retirer le filtre ${filter.label}`"
              @click="removeFilter(filter.key, filter.value)"
            />
            <FilterTag
              v-if="query.date"
              :label="library.activeDate.value"
              :aria-label="`Retirer le filtre ${library.activeDate.value}`"
              @click="library.update({ date: '' })"
            />
            <FilterTag
              v-for="key in activeToggles"
              :key="key"
              :label="TOGGLES[key].label"
              :aria-label="`Retirer le filtre ${TOGGLES[key].label}`"
              @click="setToggles(more.filter((value) => value !== key))"
            />
          </ActiveFilters>

          <ResultsBar
            ref="resultsBar"
            v-model:sort="sort"
            :noun="{ one: 'ressource', many: 'ressources' }"
            :from="library.from.value"
            :to="library.to.value"
            :total="library.total.value"
            :last-page="library.lastPage.value"
            :filtered="library.filtered.value"
            :overall="libraryTotal"
            suffix="dans la bibliothèque"
            :sort-options="SORT_OPTIONS"
          />

          <div v-if="!library.loaded.value" class="resources-loading">
            <q-spinner color="primary" size="32px" />
          </div>

          <EmptyState
            v-else-if="!library.resources.value.length"
            icon="book"
            title="Aucune ressource ne correspond"
            text="Essayez un autre mot-clé ou retirez un filtre."
            class="resources-empty"
          >
            <AppButton
              v-if="library.filtered.value"
              variant="outline"
              label="Réinitialiser les filtres"
              @click="library.reset"
            />
          </EmptyState>

          <template v-else>
            <ul class="resources-grid" :class="{ 'resources-grid--busy': library.loading.value }">
              <li v-for="resource in library.resources.value" :key="resource.id">
                <ResourceCard
                  :resource="resource"
                  @toggle-favorite="library.toggleFavorite(resource)"
                />
              </li>
            </ul>

            <ListPager
              items-label="ressources"
              :total="library.total.value"
              :page="library.page.value"
              :last-page="library.lastPage.value"
              :per-page="query.perPage"
              :per-page-options="PER_PAGE_OPTIONS"
              @update:page="changePage"
              @update:per-page="library.setPerPage"
            />
          </template>
        </div>

        <aside v-if="voices.length" class="resources-side">
          <ResourceVoices :voices="voices" />
        </aside>
      </div>
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
// Intro, filtres, grille et pagination à gauche ; avis des médiatrices dans une colonne collante
// à droite, dès le haut de la page (maquette .rs-layout). Tablette et téléphone : les avis
// passent sous la grille.
.resources-layout--with-voices {
  display: grid;
  grid-template-areas:
    'intro side'
    'main side';
  grid-template-columns: minmax(0, 1fr) 320px;
  grid-template-rows: auto 1fr;
  column-gap: 28px;
  align-items: start;
}

.resources-intro {
  grid-area: intro;
}

.resources-main {
  grid-area: main;
  min-width: 0;
}

.resources-side {
  position: sticky;
  top: calc(var(--header-h) + 20px + env(safe-area-inset-top, 0px));
  grid-area: side;
  // Aligné sur le haut du titre (marge haute de l'intro)
  margin-top: var(--s-8);
}

.resources-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 18px;
}

.resources-loading {
  display: grid;
  place-items: center;
  padding-block: var(--s-16);
}

.resources-empty {
  margin-top: 14px;
}

// Grille de 4 colonnes (maquette .rs-grid), 2 sur tablette, 1 sur téléphone
.resources-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 20px;
  margin: 14px 0 0;
  padding: 0;
  list-style: none;
  transition: opacity 0.15s;

  > li {
    display: flex;
  }

  // Nouvelle recherche en cours : la liste précédente reste visible, atténuée
  &--busy {
    opacity: 0.55;
  }
}

// À côté de la colonne des avis : 3 cartes par rangée au lieu de 4
@media (min-width: 1080px) {
  .resources-layout--with-voices .resources-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 1079px) {
  .resources-layout--with-voices {
    grid-template-areas:
      'intro'
      'main'
      'side';
    grid-template-columns: 1fr;
    grid-template-rows: auto;
  }

  .resources-side {
    position: static;
    margin-top: 0;
  }

  .resources-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

// Téléphone : une carte par ligne, pagination empilée
@media (max-width: 719px) {
  .resources-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}
</style>
