<script setup lang="ts">
// État vide (fiche Feedback) : cadre en pointillés, titre serif, phrase d'aide.
withDefaults(defineProps<{ title: string; text?: string; icon?: string }>(), {
  text: undefined,
  icon: 'leaf',
});
</script>

<template>
  <section class="empty-state" role="status">
    <q-icon :name="icon" size="28px" class="empty-state__icon" aria-hidden="true" />
    <h2 class="empty-state__title">{{ title }}</h2>
    <p v-if="text" class="empty-state__text">{{ text }}</p>
    <slot />
  </section>
</template>

<style lang="scss" scoped>
.empty-state {
  display: grid;
  justify-items: center;
  gap: var(--s-3);
  padding: var(--s-12) var(--s-6);
  border: 1px dashed var(--border-hover);
  border-radius: var(--radius-sm);
  text-align: center;

  &__icon {
    color: var(--muted);
  }

  &__title {
    font-size: var(--fs-h3);
  }

  &__text {
    max-width: 46ch;
    color: var(--ink-2);
  }
}
</style>
