<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
</script>

<template>
  <main class="not-found container">
    <p class="eyebrow">Erreur 404</p>
    <h1>Page introuvable</h1>
    <p class="not-found__text">Cette adresse ne correspond à aucune page. Vérifiez le lien.</p>
    <AppButton label="Retour à l’accueil" :to="{ name: 'home' }" />
  </main>
</template>

<style lang="scss" scoped>
.not-found {
  display: grid;
  justify-items: start;
  gap: var(--s-4);
  padding-block: var(--s-16);

  &__text {
    color: var(--ink-2);
  }
}
</style>
