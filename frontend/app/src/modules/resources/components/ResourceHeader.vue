<script setup lang="ts">
// En-tête de la fiche ressource (maquette .fe-head .fr-head) : couverture générée, type, titre,
// éditeur · date · langues · longueur, thématiques ; les actions arrivent par le slot « actions ».
// Les médiatrices et l'événement liés sont présentés juste dessous (« Autour de cette ressource »).
import { computed } from 'vue';

import { resourceMeta, themeTone, type ResourceDetail } from '../services/resources';
import ResourceCover from './ResourceCover.vue';
import ResourceTypeBadge from './ResourceTypeBadge.vue';

const props = defineProps<{ resource: ResourceDetail }>();

const publisher = computed(() => props.resource.source ?? props.resource.author?.name ?? null);
const facts = computed(() => [
  ...(publisher.value ? [publisher.value] : []),
  ...resourceMeta(props.resource).slice(0, 1),
  ...props.resource.languages,
  ...resourceMeta(props.resource).slice(1),
]);
</script>

<template>
  <section class="resource-header" aria-labelledby="resource-title">
    <ResourceCover
      :title="resource.title"
      :source="publisher"
      :type="resource.type.label"
      :year="resource.published_on.slice(0, 4)"
      class="resource-header__cover"
    />

    <div class="resource-header__identity">
      <ResourceTypeBadge :type="resource.type" />
      <h1 id="resource-title">{{ resource.title }}</h1>
    </div>

    <div class="resource-header__details">
      <ul class="resource-header__facts" aria-label="Informations">
        <li v-for="fact in facts" :key="fact">{{ fact }}</li>
      </ul>

      <ul v-if="resource.themes.length" class="resource-header__themes" aria-label="Thématiques">
        <li v-for="theme in resource.themes" :key="theme.id">
          <q-chip
            :ripple="false"
            :class="['resource-header__theme', `resource-header__theme--${themeTone(theme.id)}`]"
          >
            {{ theme.name }}
          </q-chip>
        </li>
      </ul>
    </div>

    <div v-if="$slots.actions" class="resource-header__actions">
      <slot name="actions" />
    </div>
  </section>
</template>

<style lang="scss" scoped>
.resource-header {
  // Titre et actions à côté de la couverture, informations et thématiques dessous sur toute la
  // largeur restante (sous les actions)
  display: grid;
  grid-template-areas:
    'cover identity actions'
    'cover details details';
  grid-template-columns: 210px minmax(0, 1fr) auto;
  grid-template-rows: auto 1fr;
  column-gap: 36px;
  align-items: start;
  padding-block: 22px 30px;

  &__cover {
    grid-area: cover;
  }

  &__details {
    grid-area: details;
    min-width: 0;
  }

  &__identity {
    grid-area: identity;
    min-width: 0;
    padding-top: 4px;

    h1 {
      margin-top: 12px;
      font-size: var(--fs-h1);
      line-height: 1.02;
      letter-spacing: -0.012em;
    }
  }

  &__facts {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px 0;
    margin: 14px 0 0;
    padding: 0;
    list-style: none;
    font-size: var(--fs-body);
    color: var(--ink-2);

    li + li::before {
      content: '·';
      margin-inline: 9px;
      color: var(--muted);
    }
  }

  &__themes {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 16px 0 0;
    padding: 0;
    list-style: none;
  }

  &__theme.q-chip {
    height: 28px;
    margin: 0;
    padding: 0 12px;
    border-radius: var(--radius-pill);
    font-size: var(--fs-xs);
    font-weight: 500;

    &--saffron {
      background: var(--saffron-light);
      color: var(--saffron-ink);
    }

    &--petrol {
      background: var(--petrol-light);
      color: var(--petrol);
    }

    &--sage {
      background: var(--sage-light);
      color: var(--sage-ink);
    }

    &--terra {
      background: var(--terracotta-light);
      color: var(--terracotta-ink);
    }
  }

  &__actions {
    grid-area: actions;
    padding-top: 38px;
  }
}

// Tablette : couverture réduite, actions sous l'en-tête
@media (max-width: 1079px) {
  .resource-header {
    grid-template-areas:
      'cover identity'
      'cover details'
      'actions actions';
    grid-template-columns: 160px minmax(0, 1fr);
    grid-template-rows: auto 1fr auto;
    column-gap: 28px;

    &__actions {
      margin-top: 24px;
      padding-top: 0;
    }
  }
}

// Téléphone : couverture et identité côte à côte en petit ; actions dans la barre du bas
@media (max-width: 719px) {
  .resource-header {
    grid-template-areas:
      'cover identity'
      'details details';
    grid-template-columns: 96px minmax(0, 1fr);
    grid-template-rows: auto auto;
    gap: 16px;
    padding-block: 14px 20px;

    &__cover {
      padding: 10px 9px;

      :deep(b) {
        font-size: 0.8rem;
      }
    }

    &__identity h1 {
      font-size: 1.9rem;
    }

    &__facts {
      margin-top: 0;
      font-size: var(--fs-sm);
    }

    &__actions {
      display: none;
    }
  }
}
</style>
