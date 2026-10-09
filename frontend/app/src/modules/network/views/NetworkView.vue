<script setup lang="ts">
// Annuaire des médiatrices (maquette Réseau) : filtres, filtres actifs, barre de résultats,
// grille ou liste de cartes, « Afficher plus », citation. La recherche par texte passe par
// le champ du bandeau (meta.searchInPage) qui écrit ?q=… dans l'URL de la page.
import { computed, onMounted, ref, watch } from 'vue';

import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
import QuoteBlock from '@/components/QuoteBlock.vue';
import FilterMenu from '../components/FilterMenu.vue';
import MemberCard from '../components/MemberCard.vue';
import ResultsBar, { type DirectoryView } from '../components/ResultsBar.vue';
import { useMemberDirectory } from '../composables/useMemberDirectory';
import { FILTER_KEYS, FILTER_LABELS, type FilterKey, type FilterOption } from '../services/members';

const directory = useMemberDirectory();
const { query, filters } = directory;

function setSelection(key: FilterKey, values: string[]): void {
  directory.update({ selected: { ...query.value.selected, [key]: values } });
}

// « Plus de filtres » : seule la disponibilité pour l'instant (favoris et événements à venir)
const MORE_OPTIONS: FilterOption[] = [
  {
    value: 'available',
    label: 'Disponible pour collaboration',
    hint: 'Profils ouverts à de nouveaux projets',
  },
];
const more = computed({
  get: () => (query.value.available ? ['available'] : []),
  set: (values: string[]) => directory.update({ available: values.includes('available') }),
});

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

const progress = computed(() =>
  directory.total.value ? directory.members.value.length / directory.total.value : 0
);

onMounted(() => void directory.start());
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro
        title="Les médiatrices"
        lead="Découvrez les membres du réseau RIMeF et trouvez les expertises utiles à vos projets."
      />

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

      <div v-if="directory.filtered.value" class="network-active" aria-live="polite">
        <q-chip
          v-if="query.q"
          removable
          square
          class="network-active__chip"
          :label="`« ${query.q} »`"
          :remove-aria-label="`Retirer la recherche ${query.q}`"
          @remove="directory.update({ q: '' })"
        />
        <q-chip
          v-for="filter in directory.activeFilters.value"
          :key="`${filter.key}-${filter.value}`"
          removable
          square
          class="network-active__chip"
          :label="filter.label"
          :remove-aria-label="`Retirer le filtre ${filter.label}`"
          @remove="removeFilter(filter.key, filter.value)"
        />
        <q-chip
          v-if="query.available"
          removable
          square
          class="network-active__chip"
          label="Disponible pour collaboration"
          remove-aria-label="Retirer le filtre Disponible pour collaboration"
          @remove="more = []"
        />
      </div>

      <ResultsBar
        v-model:view="view"
        v-model:sort="sort"
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
        <q-btn
          v-if="directory.filtered.value"
          outline
          no-caps
          color="primary"
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
            <MemberCard :member="member" />
          </li>
        </ul>

        <div v-if="directory.hasMore.value" class="network-more">
          <q-linear-progress
            :value="progress"
            color="secondary"
            track-color="transparent"
            size="2px"
            class="network-more__progress"
            aria-hidden="true"
          />
          <p class="network-more__count">
            {{ directory.members.value.length }} profils affichés sur {{ directory.total.value }}
          </p>
          <q-btn
            outline
            no-caps
            color="primary"
            label="Afficher plus de médiatrices"
            :loading="directory.loading.value"
            @click="directory.loadMore"
          />
        </div>
      </template>

      <QuoteBlock
        text="Un réseau pensé par et pour les médiatrices francophones."
        class="network-quote"
      />
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.network-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 14px;
}

.network-active {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;

  &__chip {
    height: 26px;
    margin: 0;
    padding: 0 6px 0 10px;
    border-radius: var(--radius-xs);
    background: var(--paper);
    box-shadow: inset 0 0 0 1px var(--border);
    font-size: var(--fs-xs);
    color: var(--ink-2);

    :deep(.q-chip__icon--remove) {
      font-size: 12px;
      opacity: 1;
    }
  }
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

  &--list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  // Nouvelle recherche en cours : la liste précédente reste visible, atténuée
  &--busy {
    opacity: 0.55;
  }
}

.network-more {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  margin-top: 36px;

  &__progress {
    width: 180px;
    max-width: 100%;
    border-radius: 2px;
    background: var(--border);
  }

  &__count {
    font-size: var(--fs-xs);
    color: var(--muted);
    font-variant-numeric: tabular-nums;
  }
}

.network-quote {
  margin-top: var(--s-12);
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

// Téléphone : filtres en bande défilante, grille sur deux colonnes
@media (max-width: 719px) {
  .network-filters {
    flex-wrap: nowrap;
    margin-inline: calc(var(--gutter) * -1);
    padding-inline: var(--gutter);
    overflow-x: auto;
    scrollbar-width: none;

    &::-webkit-scrollbar {
      display: none;
    }
  }

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
