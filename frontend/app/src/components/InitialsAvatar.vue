<script setup lang="ts">
// Avatar à initiales (fiche Avatar), en attendant les photos des membres.
// Décoratif : le nom est toujours écrit à côté ou dans une phrase.
import { computed } from 'vue';

const props = withDefaults(defineProps<{ name: string; size?: number }>(), { size: 36 });

const initials = computed(() =>
  props.name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
);
</script>

<template>
  <q-avatar :size="`${size}px`" class="initials-avatar" aria-hidden="true">
    {{ initials }}
  </q-avatar>
</template>

<style lang="scss" scoped>
.initials-avatar {
  flex: none;
  background: var(--petrol-light);
  color: var(--petrol);
  font-weight: 600;

  // q-avatar dimensionne tout en em : on réduit seulement le texte
  :deep(.q-avatar__content) {
    font-size: 0.36em;
  }
}
</style>
