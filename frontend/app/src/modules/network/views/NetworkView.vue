<script setup lang="ts">
// Annuaire des médiatrices (maquette Réseau) : intro et citation, filtres, filtres actifs,
// barre de résultats, grille ou liste de cartes, pagination. La recherche par texte
// passe par le champ du bandeau (meta.searchInPage) qui écrit ?q=… dans l'URL de la page.
import { type ComponentPublicInstance, computed, onMounted, ref, watch } from 'vue';

import AppButton from '@/components/AppButton.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterMenu from '@/components/FilterMenu.vue';
import FilterTag from '@/components/FilterTag.vue';
import ListPager from '@/components/ListPager.vue';
import PageIntro from '@/components/PageIntro.vue';
import QuoteBlock from '@/components/QuoteBlock.vue';
import MemberCard from '../components/MemberCard.vue';
import ResultsBar, { type DirectoryView } from '../components/ResultsBar.vue';
import { useMemberDirectory } from '../composables/useMemberDirectory';
import {
  FILTER_KEYS,
  FILTER_LABELS,
  PER_PAGE_OPTIONS,
  TOGGLE_KEYS,
  TOGGLES,
  type FilterKey,
  type FilterOption,
  type ToggleKey,
} from '../services/members';

const directory = useMemberDirectory();
const { query, filters } = directory;

function setSelection(key: FilterKey, values: string[]): void {
  directory.update({ selected: { ...query.value.selected, [key]: values } });
}

// « Plus de filtres » : critères oui / non (disponible, prochain événement, favoris)
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
  directory.update({
    toggles: Object.fromEntries(TOGGLE_KEYS.map((key) => [key, values.includes(key)])) as Record<
      ToggleKey,
      boolean
    >,
  });
}

const activeToggles = computed(() => TOGGLE_KEYS.filter((key) => query.value.toggles[key]));

function removeFilter(key: FilterKey, value: string): void {
  setSelection(
    key,
    query.value.selected[key].filter((item) => item !== value)
  );
}

// Grille ou liste : préférence de chaque appareil (sans stockage possible, grille)
const VIEW_STORAGE_KEY = 'rimef-reseau-vue';
const view = ref<DirectoryView>(readView());

function readView(): DirectoryView {
  try {
    return localStorage.getItem(VIEW_STORAGE_KEY) === 'list' ? 'list' : 'grid';
  } catch {
    return 'grid';
  }
}

watch(view, (value) => {
  try {
    localStorage.setItem(VIEW_STORAGE_KEY, value);
  } catch {
    // Stockage indisponible (navigation privée) : la préférence n'est pas retenue
  }
});

const sort = computed({
  get: () => query.value.sort,
  set: (value) => directory.update({ sort: value }),
});

// Changement de page : retour en douceur en haut des résultats (barre de tri visible)
const resultsBar = ref<ComponentPublicInstance | null>(null);

function changePage(page: number): void {
  directory.goToPage(page);
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  (resultsBar.value?.$el as HTMLElement | undefined)?.scrollIntoView({
    behavior: reduceMotion ? 'auto' : 'smooth',
    block: 'start',
  });
}

onMounted(() => void directory.start());
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro
        title="Les médiatrices"
        lead="Découvrez les membres du réseau RIMeF et trouvez les expertises utiles à vos projets."
      >
        <template #aside>
          <QuoteBlock
            variant="card"
            text="Un réseau pensé par et pour les médiatrices francophones."
            class="network-quote"
          />
        </template>
        <template #below>
          <div class="network-filters" aria-label="Filtres" role="group">
            <FilterMenu
              v-for="key in FILTER_KEYS"
              :key="key"
              :label="FILTER_LABELS[key]"
              :options="filters?.[key] ?? []"
              :model-value="query.selected[key]"
              @update:model-value="(values: string[]) => setSelection(key, values)"
            />
            <FilterMenu
              v-model="more"
              label="Plus de filtres"
              icon="adjustments-horizontal"
              section="Profil"
              ghost
              :options="MORE_OPTIONS"
            />
          </div>
        </template>
      </PageIntro>

      <div v-if="directory.filtered.value" class="network-active" aria-live="polite">
        <FilterTag
          v-if="query.q"
          :label="`« ${query.q} »`"
          :aria-label="`Retirer la recherche ${query.q}`"
          @click="directory.update({ q: '' })"
        />
        <FilterTag
          v-for="filter in directory.activeFilters.value"
          :key="`${filter.key}-${filter.value}`"
          :label="filter.label"
          :aria-label="`Retirer le filtre ${filter.label}`"
          @click="removeFilter(filter.key, filter.value)"
        />
        <FilterTag
          v-for="key in activeToggles"
          :key="key"
          :label="TOGGLES[key].label"
          :aria-label="`Retirer le filtre ${TOGGLES[key].label}`"
          @click="setToggles(more.filter((value) => value !== key))"
        />
      </div>

      <ResultsBar
        ref="resultsBar"
        v-model:view="view"
        v-model:sort="sort"
        class="network-results"
        :count="directory.total.value"
        :directory-total="directory.directoryTotal.value"
        :filtered="directory.filtered.value"
        @reset="directory.reset"
      />

      <div v-if="!directory.loaded.value" class="network-loading">
        <q-spinner color="primary" size="32px" />
      </div>

      <EmptyState
        v-else-if="!directory.members.value.length"
        icon="users"
        title="Aucune médiatrice ne correspond"
        text="Essayez un autre mot-clé ou retirez un filtre pour élargir la recherche."
        class="network-empty"
      >
        <AppButton
          v-if="directory.filtered.value"
          variant="outline"
          label="Réinitialiser les filtres"
          @click="directory.reset"
        />
      </EmptyState>

      <template v-else>
        <ul
          class="member-grid"
          :class="{
            'member-grid--list': view === 'list',
            'member-grid--busy': directory.loading.value,
          }"
        >
          <li v-for="member in directory.members.value" :key="member.id">
            <MemberCard :member="member" @toggle-favorite="directory.toggleFavorite(member)" />
          </li>
        </ul>

        <ListPager
          noun="Médiatrices"
          items-label="médiatrices"
          :total="directory.total.value"
          :from="directory.from.value"
          :to="directory.to.value"
          :page="directory.page.value"
          :last-page="directory.lastPage.value"
          :per-page="directory.query.value.perPage"
          :per-page-options="PER_PAGE_OPTIONS"
          @update:page="changePage"
          @update:per-page="directory.setPerPage"
        />
      </template>
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.network-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.network-active {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.network-results {
  // Arrivée par le changement de page : la barre ne passe pas sous l'en-tête fixe
  scroll-margin-top: calc(var(--header-h) + 16px);
}

.network-loading {
  display: grid;
  place-items: center;
  padding-block: var(--s-16);
}

.network-empty {
  margin-top: 20px;
}

.member-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 24px;
  margin: 20px 0 0;
  padding: 0;
  list-style: none;
  transition: opacity 0.15s;

  > li {
    display: flex;
  }

  // Liste : deux bandeaux côte à côte sur ordinateur, un seul par ligne sur tablette
  &--list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }

  // Nouvelle recherche en cours : la liste précédente reste visible, atténuée
  &--busy {
    opacity: 0.55;
  }
}
// Citation compacte à droite de l'intro : même hauteur que le titre et sa phrase
.network-quote.q-card {
  padding: 24px 28px;

  :deep(.quote-block__text) {
    max-width: none;
    font-size: 1.35rem;
  }

  :deep(.quote-block__cite) {
    margin-top: 12px;
  }

  :deep(.quote-block__leaf) {
    width: 90px;
    height: 102px;
  }
}

// Tablette
@media (max-width: 1079px) {
  .member-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;

    &--list {
      grid-template-columns: 1fr;
    }
  }
}

// Téléphone : grille sur deux colonnes (les filtres passent à la ligne, tous visibles)
@media (max-width: 719px) {
  .member-grid,
  .member-grid--list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 16px;
  }
}

// Petits téléphones : une carte horizontale par ligne
@media (max-width: 560px) {
  .member-grid,
  .member-grid--list {
    grid-template-columns: 1fr;
  }
}
</style>
