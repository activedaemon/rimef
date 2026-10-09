<script setup lang="ts">
// Filtre en pilule (fiche FilterChip) : menu déroulant sur ordinateur, feuille en bas
// d'écran sous 720 px. Les cases s'appliquent tout de suite ; « Afficher les résultats »
// ferme seulement la liste.
import { useQuasar } from 'quasar';
import { computed, ref } from 'vue';

import type { FilterOption } from '../services/members';
import FilterOptions from './FilterOptions.vue';

withDefaults(
  defineProps<{
    label: string;
    options: FilterOption[];
    /** Variante fantôme (« Plus de filtres »), avec une icône avant le libellé. */
    ghost?: boolean;
    icon?: string;
    /** Sur-titre de la liste (« Profil »). */
    section?: string;
  }>(),
  { ghost: false, icon: undefined, section: undefined }
);

const selected = defineModel<string[]>({ required: true });

const $q = useQuasar();
const open = ref(false);

const isSheet = computed(() => $q.screen.lt.sm);
const count = computed(() => selected.value.length);
</script>

<template>
  <div class="filter-menu">
    <q-btn
      unelevated
      no-caps
      class="filter-chip"
      :class="{
        'filter-chip--ghost': ghost,
        'filter-chip--active': count > 0,
        'filter-chip--open': open,
      }"
      :aria-expanded="open ? 'true' : 'false'"
      aria-haspopup="dialog"
      @click="open = !open"
    >
      <q-icon v-if="icon" :name="icon" size="14px" class="filter-chip__icon" />
      <span>{{ label }}</span>
      <span v-if="count" class="filter-chip__count">{{ count }}</span>
      <q-icon v-if="!ghost" name="chevron-down" size="14px" class="filter-chip__chevron" />

      <q-menu
        v-if="!isSheet"
        v-model="open"
        no-parent-event
        anchor="bottom left"
        self="top left"
        :offset="[0, 6]"
        class="filter-pop"
        role="dialog"
        :aria-label="label"
      >
        <FilterOptions v-model="selected" :options="options" :section="section" />
      </q-menu>
    </q-btn>

    <q-dialog v-if="isSheet" v-model="open" position="bottom">
      <q-card class="filter-sheet" role="dialog" :aria-label="label">
        <div class="filter-sheet__head">
          <h2 class="filter-sheet__title">{{ label }}</h2>
          <q-btn v-close-popup flat round dense icon="x" aria-label="Fermer" />
        </div>
        <FilterOptions v-model="selected" :options="options" :section="section" comfortable />
      </q-card>
    </q-dialog>
  </div>
</template>

<style lang="scss" scoped>
// Pilule : fond paper, filet ; ouverte : pétrole ; active : fond petrol-light + compteur plein
.filter-chip {
  min-height: 36px;
  padding: 0 12px 0 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-pill);
  background: var(--paper);
  color: var(--ink-2);
  font-size: var(--fs-sm);
  font-weight: 500;

  // Pas de voile gris de Quasar au survol ou à l'ouverture : la pilule a ses propres états
  :deep(.q-focus-helper) {
    display: none;
  }

  :deep(.q-btn__content) {
    flex-wrap: nowrap;
    gap: 6px;
    white-space: nowrap;
  }

  &__icon,
  &__chevron {
    color: var(--muted);
    transition: transform 0.15s;
  }

  &__count {
    display: inline-grid;
    place-items: center;
    min-width: 18px;
    height: 18px;
    padding-inline: 5px;
    border-radius: var(--radius-pill);
    background: var(--petrol);
    color: var(--paper);
    font-size: 10.5px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
  }

  &:hover {
    border-color: var(--border-hover);
    color: var(--ink);
  }

  &--open {
    border-color: var(--petrol);
    color: var(--petrol);

    .filter-chip__chevron {
      transform: rotate(180deg);
    }
  }

  &--active {
    border-color: transparent;
    background: var(--petrol-light);
    color: var(--petrol);

    .filter-chip__icon,
    .filter-chip__chevron {
      color: var(--petrol);
    }
  }

  &--ghost {
    border-color: transparent;
    background: transparent;
    color: var(--petrol);

    .filter-chip__icon {
      color: var(--petrol);
    }

    &:hover {
      border-color: var(--border);
      background: var(--paper);
      color: var(--petrol);
    }
  }
}

.filter-sheet {
  width: 100%;
  max-height: 78vh;
  padding: 8px 16px calc(16px + env(safe-area-inset-bottom, 0px));
  border-radius: var(--radius-md) var(--radius-md) 0 0 !important;
  background: var(--card);

  &__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
    padding: 8px 0 10px;
    border-bottom: 1px solid var(--border);
  }

  &__title {
    font-size: var(--fs-h3);
  }
}
</style>

<style lang="scss">
// Le menu est téléporté hors du composant : style non scopé
.filter-pop {
  min-width: 260px;
  max-width: 320px;
  padding: 8px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--card);
  box-shadow: var(--shadow-float);
}
</style>
