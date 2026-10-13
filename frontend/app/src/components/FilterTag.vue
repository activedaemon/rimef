<script setup lang="ts">
// Filtre actif retirable (maquette .tag-x) : toute la pastille retire le filtre au clic.
// aria-label conseillé : « Retirer le filtre … ».
defineProps<{ label: string }>();
</script>

<template>
  <q-btn flat dense no-caps :ripple="false" icon-right="x" :label="label" class="filter-tag" />
</template>

<style lang="scss" scoped>
.filter-tag.q-btn {
  min-height: 26px;
  height: 26px;
  padding: 0 6px 0 10px;
  border-radius: var(--radius-xs);
  // Mêmes couleurs que la pilule de filtre active (FilterMenu)
  background: var(--petrol-light);
  box-shadow: none;
  font-size: var(--fs-xs);
  font-weight: 400;
  color: var(--petrol);
  transition:
    color 0.15s,
    box-shadow 0.15s;

  :deep(.q-focus-helper) {
    display: none;
  }

  :deep(.q-icon) {
    margin-left: 6px;
    font-size: 12px;
  }

  &:hover {
    box-shadow: inset 0 0 0 1px var(--petrol);
  }

  &:focus-visible {
    outline: 2px solid var(--saffron);
    outline-offset: 2px;
  }
}
</style>
