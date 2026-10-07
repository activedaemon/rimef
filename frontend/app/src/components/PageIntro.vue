<script setup lang="ts">
// Intro de page : sur-titre optionnel, titre serif, phrase d'accompagnement (58 caractères max).
defineProps<{ title: string; lead?: string; eyebrow?: string }>();
</script>

<template>
  <header class="page-intro">
    <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
    <h1 class="page-intro__title">{{ title }}</h1>
    <p v-if="lead" class="page-intro__lead">{{ lead }}</p>
    <slot />
  </header>
</template>

<style lang="scss" scoped>
.page-intro {
  display: grid;
  justify-items: start;
  gap: var(--s-3);
  padding-block: var(--s-10) var(--s-8);

  &__title {
    line-height: 1.02;
    letter-spacing: -0.012em;
  }

  &__lead {
    max-width: 58ch;
    font-size: var(--fs-lead);
    color: var(--ink-2);
  }
}

@media (max-width: 719px) {
  .page-intro {
    padding-block: var(--s-8) var(--s-6);
  }
}
</style>
