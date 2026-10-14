<script setup lang="ts">
// Ressources liées à un événement (maquette Fiche événement, onglet « Ressources liées » .res) :
// type, titre (vers la fiche ressource), éditeur · date · durée, ce qui relie la ressource à
// l'événement, appel à l'action.
import ResourceRow from '@modules/resources/components/ResourceRow.vue';
import { resourceMeta } from '@modules/resources/services/resources';
import type { EventResource } from '../services/events';

defineProps<{ resources: EventResource[] }>();

function facts(resource: EventResource): string {
  return [resource.source, ...resourceMeta(resource)].filter(Boolean).join(' · ');
}
</script>

<template>
  <ul class="event-resources">
    <ResourceRow
      v-for="resource in resources"
      :key="resource.id"
      :slug="resource.slug"
      :title="resource.title"
      :type="resource.type"
      :published-on="resource.published_on"
      :source="resource.source"
      :eyebrow="resource.type.label"
      :facts="facts(resource)"
      :note="resource.note"
    />
  </ul>
</template>

<style lang="scss" scoped>
.event-resources {
  display: grid;
  margin: 0;
  padding: 0;
  list-style: none;
}
</style>
