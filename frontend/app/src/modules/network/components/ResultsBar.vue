<script setup lang="ts">
// Barre de résultats (fiche ResultsBar) : nombre de médiatrices, « Tout effacer » quand la
// liste est filtrée, bascule grille / liste (masquée sous 720 px) et tri.
import { mediatorNoun } from '@/lib/wording';
import { SORT_OPTIONS, type SortKey } from '../services/members';

export type DirectoryView = 'grid' | 'list';

defineProps<{ count: number; directoryTotal: number; filtered: boolean }>();
const emit = defineEmits<{ reset: [] }>();

const view = defineModel<DirectoryView>('view', { required: true });
const sort = defineModel<SortKey>('sort', { required: true });

const VIEW_OPTIONS = [
  { value: 'grid', icon: 'layout-grid', attrs: { 'aria-label': 'Grille' } },
  { value: 'list', icon: 'list', attrs: { 'aria-label': 'Liste' } },
];
</script>

<template>
  <div class="results-bar">
    <p class="results-bar__count" aria-live="polite">
      <template v-if="filtered">
        <b>{{ count }}</b> {{ mediatorNoun(count) }} sur {{ directoryTotal }}
        <q-btn
          flat
          dense
          no-caps
          class="link-more results-bar__reset"
          label="Tout effacer"
          @click="emit('reset')"
        />
      </template>
      <template v-else>
        <b>{{ directoryTotal }}</b> {{ mediatorNoun(directoryTotal) }}
      </template>
    </p>

    <div class="results-bar__tools">
      <q-btn-toggle
        v-model="view"
        :options="VIEW_OPTIONS"
        flat
        dense
        class="results-bar__view gt-xs"
        color="grey-7"
        toggle-color="primary"
        role="group"
        aria-label="Affichage"
      />

      <q-select
        v-model="sort"
        :options="SORT_OPTIONS"
        emit-value
        map-options
        dense
        outlined
        options-dense
        class="results-bar__sort"
        aria-label="Trier par"
      >
        <template #prepend>
          <span class="results-bar__sort-label gt-xs">Trier par :</span>
        </template>
      </q-select>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.results-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-top: 22px;
  padding-top: 18px;
  border-top: 1px solid var(--border);

  &__count {
    font-size: var(--fs-body);
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

  &__tools {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  &__view {
    padding: 2px;
    border-radius: var(--radius-xs);

    :deep(.q-btn) {
      width: 34px;
      height: 34px;
      border-radius: var(--radius-xs);
    }

    :deep(.q-btn .q-icon) {
      font-size: 16px;
    }

    :deep(.q-btn[aria-pressed='true']) {
      background: var(--paper);
      box-shadow: inset 0 0 0 1px var(--border);
    }
  }

  &__sort {
    min-width: 150px;
    font-size: var(--fs-xs);

    :deep(.q-field__control) {
      min-height: 36px;
      border-radius: var(--radius-xs);
      background: var(--paper);
    }

    :deep(.q-field__control::before) {
      border-color: var(--border);
    }

    :deep(.q-field__native) {
      font-weight: 500;
      color: var(--ink);
    }
  }

  &__sort-label {
    font-size: var(--fs-xs);
    color: var(--ink-2);
  }
}

@media (max-width: 719px) {
  .results-bar {
    margin-top: 16px;
    padding-top: 14px;

    &__sort {
      min-width: 110px;
    }
  }
}
</style>
