<script setup lang="ts">
// Lien de retour en haut des fiches (fiche BackLink) : « Retour à l'annuaire »…
import type { RouteLocationRaw } from 'vue-router';

defineProps<{ to: RouteLocationRaw; label: string }>();
</script>

<template>
  <router-link :to="to" class="back-link">
    <q-icon name="arrow-left" size="16px" aria-hidden="true" />
    {{ label }}
  </router-link>
</template>

<style lang="scss" scoped>
.back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 44px;
  font-size: var(--fs-sm);
  color: var(--ink-2);
  text-decoration: none;
  transition: color 0.15s;

  &:hover {
    color: var(--link-hover);
  }
}
</style>
