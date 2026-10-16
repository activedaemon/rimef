<script setup lang="ts">
// Ligne des filtres actifs des listes (Réseau, Agenda, Ressources, Administration) : pastilles
// « Retirer le filtre … » puis, s'il y a au moins un filtre, « Retirer les filtres » (tout
// retirer), à côté de ce qu'il retire. Toujours présente, à la hauteur d'une pastille même vide :
// cocher le premier filtre ou retirer le dernier ne décale plus la liste. Annoncée aux lecteurs
// d'écran (les pastilles seulement : le bouton de fin n'est pas une information).
defineProps<{ filtered: boolean }>();
const emit = defineEmits<{ clear: [] }>();
</script>

<template>
  <div class="active-filters">
    <div class="active-filters__tags" aria-live="polite">
      <slot />
    </div>
    <q-btn
      v-if="filtered"
      flat
      dense
      no-caps
      class="link-more active-filters__clear"
      label="Retirer les filtres"
      @click="emit('clear')"
    />
  </div>
</template>

<style lang="scss" scoped>
.active-filters {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  align-content: flex-start;
  gap: 8px 12px;
  // Hauteur d'une pastille FilterTag : l'espace reste réservé quand aucun filtre n'est actif
  min-height: 26px;
  margin-top: 12px;

  &__tags {
    display: contents;
  }

  &__clear.q-btn {
    min-height: 26px;
    padding: 0 4px;
    font-size: var(--fs-sm);
  }
}
</style>
