<script setup lang="ts">
// Avatar d'une médiatrice (fiche Avatar) : sa photo si elle en a une, sinon ses initiales
// (aussi quand la photo ne se charge pas). Décoratif : le nom est toujours écrit à côté.
import { computed, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ name: string; photo?: string | null; size?: number }>(), {
  photo: null,
  size: 36,
});

const failed = ref(false);
watch(
  () => props.photo,
  () => (failed.value = false)
);

const showPhoto = computed(() => props.photo !== null && !failed.value);

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
  <q-avatar :size="`${size}px`" class="member-avatar" aria-hidden="true">
    <img
      v-if="showPhoto"
      :src="photo!"
      alt=""
      loading="lazy"
      decoding="async"
      :width="size"
      :height="size"
      @error="failed = true"
    />
    <template v-else>{{ initials }}</template>
  </q-avatar>
</template>

<style lang="scss" scoped>
.member-avatar {
  flex: none;
  background: var(--petrol-light);
  color: var(--petrol);
  font-weight: 600;

  // q-avatar dimensionne tout en em : on réduit seulement le texte
  :deep(.q-avatar__content) {
    font-size: 0.36em;
  }

  img {
    object-fit: cover;
    object-position: 50% 30%;
  }
}
</style>
