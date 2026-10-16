<script setup lang="ts" generic="T extends number">
// Pagination des listes (Réseau, Agenda, Ressources, Administration) : sur une seule ligne, le
// choix du nombre par page à gauche et les numéros à droite (AppPagination). La plage
// (« Ressources 1–9 sur 20 ») est dans la barre de résultats, au-dessus de la liste (ResultRange).
// La barre n'apparaît qu'au-delà de la plus petite taille proposée, les numéros que s'il y a
// plusieurs pages. Sur téléphone, les numéros passent sous le sélecteur.
import { computed } from 'vue';

import AppPagination from './AppPagination.vue';

const props = defineProps<{
  /** Nom de l'élément pour les libellés d'accessibilité : « ressources », « événements »… */
  itemsLabel: string;
  total: number;
  page: number;
  lastPage: number;
  perPage: T;
  perPageOptions: readonly T[];
}>();

const emit = defineEmits<{ 'update:page': [page: number]; 'update:perPage': [perPage: T] }>();

const choices = computed(() =>
  props.perPageOptions.map((value) => ({ label: `${value} par page`, value }))
);
const showBar = computed(() => props.total > (props.perPageOptions[0] ?? 0));
</script>

<template>
  <div v-if="total && showBar" class="list-pager">
    <nav class="list-pager__bar" :aria-label="`Pagination des ${itemsLabel}`">
      <q-select
        :model-value="perPage"
        :options="choices"
        emit-value
        map-options
        dense
        outlined
        options-dense
        class="rimef-select list-pager__size"
        :aria-label="`Nombre de ${itemsLabel} par page`"
        @update:model-value="(value: T) => emit('update:perPage', value)"
      />
      <AppPagination
        v-if="lastPage > 1"
        :model-value="page"
        :max="lastPage"
        class="list-pager__pages"
        @update:model-value="(value: number) => emit('update:page', value)"
      />
    </nav>
  </div>
</template>

<style lang="scss" scoped>
.list-pager {
  // Taille de page à gauche, pages à droite, sur une seule ligne
  &__bar {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
  }

  &__size {
    flex: none;
    min-width: 140px;
  }

  &__pages {
    margin-left: auto;
  }
}

// Téléphone : sélecteur à gauche, pagination dessous sur toute la largeur
@media (max-width: 719px) {
  .list-pager {
    &__bar {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
    }

    &__pages {
      margin-left: 0;
    }
  }
}
</style>
