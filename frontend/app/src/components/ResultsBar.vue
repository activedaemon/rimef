<script setup lang="ts" generic="S extends string">
// Barre de résultats des listes paginées (fiche ResultsBar) : Réseau, Agenda, Ressources,
// Administration. Une seule écriture partout :
// - plusieurs pages : « Médiatrices 13–24 sur 32 » (lu « 13 à 24 ») ;
// - une seule page : « 5 médiatrices » ;
// - liste filtrée : « … · 32 médiatrices au total » (ou le complément de la page : « à venir »,
//   « dans la bibliothèque »). « Retirer les filtres » est dans la ligne des filtres actifs
//   (ActiveFilters). Seule la phrase est annoncée (aria-live) ; l'emplacement « actions »
//   (ex. « 2 invitations en attente ») est à côté, hors de la région annoncée. À droite : bascule Grille / Liste si la
// page la propose (v-model:view), puis le tri.
import { computed } from 'vue';

import AppTooltip from './AppTooltip.vue';

export type DirectoryView = 'grid' | 'list';

const props = withDefaults(
  defineProps<{
    /** Nom accordé : { one: 'médiatrice', many: 'médiatrices' }. */
    noun: { one: string; many: string };
    from: number;
    to: number;
    /** Éléments de la liste (filtres compris). */
    total: number;
    lastPage: number;
    filtered: boolean;
    /** Éléments sans filtre (« 32 médiatrices au total »). */
    overall?: number | null;
    /** Complément du total de la page (« à venir », « dans la bibliothèque »). */
    suffix?: string;
    sortOptions: readonly { value: S; label: string }[];
  }>(),
  { overall: null, suffix: undefined }
);

const sort = defineModel<S>('sort', { required: true });
/** Bascule Grille / Liste, seulement si la page la propose. */
const view = defineModel<DirectoryView>('view');

const VIEW_OPTIONS = [
  { value: 'grid', icon: 'layout-grid', slot: 'grid', attrs: { 'aria-label': 'Grille' } },
  { value: 'list', icon: 'list', slot: 'list', attrs: { 'aria-label': 'Liste' } },
];

function nounFor(count: number): string {
  return count > 1 ? props.noun.many : props.noun.one;
}

const paged = computed(() => props.lastPage > 1 && props.total > 0);
const capitalized = computed(
  () => props.noun.many.charAt(0).toUpperCase() + props.noun.many.slice(1)
);
/** Complément du total : « à venir » sans filtre ; avec filtre, il qualifie le total général. */
const overallText = computed(() => {
  if (!props.filtered || props.overall == null) return '';
  return `${props.overall} ${nounFor(props.overall)} ${props.suffix ?? 'au total'}`;
});
</script>

<template>
  <div class="results-bar">
    <div class="results-bar__summary">
      <p class="results-bar__count" aria-live="polite">
        <template v-if="paged">
          {{ capitalized }}
          <b aria-hidden="true">{{ from }}–{{ to }}</b
          ><span class="sr-only">{{ from }} à {{ to }}</span> sur {{ total }}
        </template>
        <template v-else>
          <b>{{ total }}</b> {{ nounFor(total) }}
        </template>
        <template v-if="!filtered && suffix">{{ ` ${suffix}` }}</template>
        <template v-if="overallText">
          <span aria-hidden="true"> · </span><span class="sr-only">, </span>{{ overallText }}
        </template>
      </p>
      <slot name="actions" />
    </div>

    <div class="results-bar__tools">
      <q-btn-toggle
        v-if="view !== undefined"
        v-model="view"
        :options="VIEW_OPTIONS"
        flat
        dense
        class="results-bar__view gt-xs"
        color="grey-7"
        toggle-color="primary"
        role="group"
        aria-label="Affichage"
      >
        <template #grid><AppTooltip>Afficher en grille</AppTooltip></template>
        <template #list><AppTooltip>Afficher en liste</AppTooltip></template>
      </q-btn-toggle>

      <q-select
        v-model="sort"
        :options="sortOptions"
        emit-value
        map-options
        dense
        outlined
        options-dense
        class="rimef-select results-bar__sort"
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
  // Arrivée par le changement de page : la barre ne passe pas sous l'en-tête fixe
  scroll-margin-top: calc(var(--header-h) + 16px);

  &__summary {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 4px 12px;
    min-width: 0;
  }

  &__count {
    font-size: var(--fs-body);
    color: var(--ink-2);
    font-variant-numeric: tabular-nums;

    b {
      font-weight: 600;
      color: var(--petrol);
    }
  }

  &__summary :deep(.link-more) {
    min-height: 32px;
    font-size: var(--fs-sm);
  }

  &__tools {
    display: flex;
    flex: none;
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
    min-width: 170px;
  }

  &__sort-label {
    font-size: var(--fs-xs);
    color: var(--ink-2);
  }
}

@media (max-width: 719px) {
  .results-bar {
    align-items: flex-start;
    margin-top: 16px;
    padding-top: 14px;

    &__sort {
      min-width: 110px;
    }
  }
}
</style>
