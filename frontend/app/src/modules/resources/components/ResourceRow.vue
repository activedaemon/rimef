<script setup lang="ts">
// Ligne de ressource des fiches (maquette .res / .row / .ct) : petite couverture, sur-titre, titre
// vers la fiche ressource, une ligne d'informations, une phrase de lien, appel à l'action
// (« Lire », « Écouter »…). Fiche événement (ressources liées) et fiche membre (contributions).
// - compact : colonne de droite (« Contributions récentes »), sans sur-titre ni appel à l'action.
import { computed } from 'vue';

import ResourceCover from './ResourceCover.vue';

const props = withDefaults(
  defineProps<{
    slug: string;
    title: string;
    type: { label: string; action: string };
    publishedOn: string;
    source?: string | null;
    eyebrow?: string;
    facts?: string;
    note?: string | null;
    compact?: boolean;
  }>(),
  { source: null, eyebrow: undefined, facts: undefined, note: null, compact: false }
);

const to = computed(() => ({ name: 'resource', params: { slug: props.slug } }));
</script>

<template>
  <li class="resource-row" :class="{ 'resource-row--compact': compact }">
    <ResourceCover
      :title="title"
      :source="source"
      :type="type.label"
      :year="publishedOn.slice(0, 4)"
      class="resource-row__cover"
    />
    <div class="resource-row__body">
      <p v-if="eyebrow && !compact" class="eyebrow">{{ eyebrow }}</p>
      <h3>
        <router-link :to="to">{{ title }}</router-link>
      </h3>
      <p v-if="facts" class="resource-row__facts">{{ facts }}</p>
      <p v-if="note && !compact" class="resource-row__note">
        <slot name="note">{{ note }}</slot>
      </p>
    </div>
    <router-link
      v-if="!compact"
      :to="to"
      class="link-more resource-row__action"
      aria-hidden="true"
      tabindex="-1"
    >
      {{ type.action }}
    </router-link>
  </li>
</template>

<style lang="scss" scoped>
.resource-row {
  display: grid;
  grid-template-columns: 64px minmax(0, 1fr) auto;
  gap: 18px;
  align-items: center;
  padding: 16px 0;

  & + & {
    border-top: 1px solid var(--border);
  }

  &:first-child {
    padding-top: 0;
  }

  &:last-child {
    padding-bottom: 0;
  }

  // Couverture réduite : le texte de la couverture n'a pas la place, seules les feuilles restent
  &__cover {
    width: 64px;
    height: 78px;
    padding: 0;

    :deep(small),
    :deep(b),
    :deep(i) {
      display: none;
    }

    :deep(.resource-cover__leaf) {
      width: 72px;
      height: 62px;
    }
  }

  &__body {
    min-width: 0;

    h3 {
      margin-top: 4px;
      font-size: 1.3rem;
      line-height: 1.15;

      a {
        color: inherit;
        text-decoration: none;

        &:hover,
        &:focus-visible {
          color: var(--petrol);
        }
      }
    }
  }

  &__facts {
    margin-top: 4px;
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &__note {
    margin-top: 6px;
    font-size: var(--fs-sm);
    color: var(--ink-2);
  }

  // Colonne de droite : couverture et titre plus petits
  &--compact {
    grid-template-columns: 40px minmax(0, 1fr);
    gap: 12px;
    padding: 10px 0;

    .resource-row__cover {
      width: 40px;
      height: 50px;

      :deep(.resource-cover__leaf) {
        width: 48px;
        height: 42px;
      }
    }

    h3 {
      margin-top: 0;
      // noinspection CssNoGenericFontName
      font-family: var(--font-ui);
      font-size: var(--fs-sm);
      font-weight: 600;
      line-height: 1.35;
    }
  }
}

// Téléphone : l'appel à l'action passe sous le texte
@media (max-width: 719px) {
  .resource-row:not(.resource-row--compact) {
    grid-template-columns: 52px minmax(0, 1fr);
    gap: 14px;

    .resource-row__cover {
      width: 52px;
      height: 64px;
    }

    .resource-row__action {
      grid-column: 2;
    }
  }
}
</style>
