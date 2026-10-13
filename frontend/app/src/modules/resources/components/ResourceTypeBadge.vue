<script setup lang="ts">
// Étiquette du type de ressource (fiche ResourceType) : rapport et analyse en sauge, podcast en
// terracotta, article en safran, vidéo en pétrole, outil cerclé sur fond ivoire.
defineProps<{ type: { value: string; label: string } }>();
</script>

<template>
  <q-badge :class="['resource-type', `resource-type--${type.value}`]">{{ type.label }}</q-badge>
</template>

<style lang="scss" scoped>
.resource-type.q-badge {
  height: 24px;
  padding: 0 10px;
  border-radius: var(--radius-xs);
  background: var(--sage-light);
  color: var(--sage-ink);
  font-size: 10.5px;
  font-weight: 600;
  letter-spacing: 0.09em;
  text-transform: uppercase;
}

.resource-type--podcast.q-badge {
  background: var(--terracotta-light);
  color: var(--terracotta-ink);
}

.resource-type--article.q-badge {
  background: var(--saffron-light);
  color: var(--saffron-ink);
}

.resource-type--video.q-badge {
  background: var(--petrol-light);
  color: var(--petrol);
}

.resource-type--tool.q-badge {
  background: var(--ivory);
  box-shadow: inset 0 0 0 1px var(--border);
  color: var(--ink-2);
}
</style>
