<script setup lang="ts">
// Ressources à découvrir (maquette Accueil .res) : les dernières ressources de la bibliothèque,
// chacune avec sa vignette (couverture générée, pastille de lecture pour un podcast ou une vidéo),
// son titre vers la fiche et « Rapport · Avril 2026 » ou « Podcast · 34 min » ; « Voir tout »
// mène à la bibliothèque. Sur tablette, le bloc prend toute la largeur : ressources sur 2 colonnes.
import ResourceCover from '@modules/resources/components/ResourceCover.vue';
import { publishedLabel } from '@modules/resources/services/resources';
import type { HomeResource } from '../services/home-feed';

defineProps<{ resources: HomeResource[] }>();

/** Ce qui s'écoute ou se regarde : pastille de lecture sur la vignette. */
const PLAYABLE = ['podcast', 'video'];

function to(resource: HomeResource) {
  return { name: 'resource', params: { slug: resource.slug } };
}

/** « Avril 2026 », ou « 34 min » quand la ressource a une durée. */
function detail(resource: HomeResource): string {
  return resource.duration_minutes
    ? `${resource.duration_minutes} min`
    : publishedLabel(resource.published_on);
}
</script>

<template>
  <q-card flat class="rimef-card home-block" tag="section" aria-labelledby="resources-title">
    <div class="card-head">
      <h2 id="resources-title" class="card-title">Ressources à découvrir</h2>
      <router-link :to="{ name: 'resources' }" class="link-more">Voir tout</router-link>
    </div>
    <p v-if="!resources.length" class="home-block__empty">Aucune ressource pour le moment.</p>
    <ul v-else class="home-block__list home-resources">
      <li v-for="resource in resources" :key="resource.id" class="home-resource">
        <router-link
          :to="to(resource)"
          class="home-resource__thumb"
          tabindex="-1"
          aria-hidden="true"
        >
          <ResourceCover
            :title="resource.title"
            :source="resource.source"
            :type="resource.type.label"
            :year="resource.published_on.slice(0, 4)"
            class="home-resource__cover"
          />
          <span v-if="PLAYABLE.includes(resource.type.value)" class="home-resource__play">
            <svg viewBox="0 0 10 10"><path d="M2 1 9 5 2 9Z" /></svg>
          </span>
        </router-link>
        <div class="home-resource__text">
          <h3 class="home-block__item-title">
            <router-link :to="to(resource)">{{ resource.title }}</router-link>
          </h3>
          <p class="home-resource__type">
            <span>{{ resource.type.label }}</span> · {{ detail(resource) }}
          </p>
        </div>
      </li>
    </ul>
  </q-card>
</template>

<style lang="scss" scoped>
@use './home-block';

.home-resource {
  display: flex;
  align-items: center;
  gap: 14px;

  &__thumb {
    position: relative;
    flex: none;
    width: 62px;
    height: 62px;
    overflow: hidden;
    border-radius: var(--radius-xs);
  }

  // Couverture générée recadrée en vignette : seules les feuilles restent
  &__cover {
    width: 62px;
    height: 62px;
    aspect-ratio: auto;
    padding: 0;
    border-radius: 0;
    box-shadow: none;

    :deep(small),
    :deep(b),
    :deep(i) {
      display: none;
    }

    :deep(.resource-cover__leaf) {
      width: 70px;
      height: 60px;
    }
  }

  &__play {
    position: absolute;
    right: 5px;
    bottom: 5px;
    display: grid;
    place-items: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: rgba(252, 250, 246, 0.92);
    color: var(--petrol);

    svg {
      width: 9px;
      height: 9px;
      fill: currentColor;
    }
  }

  &__text {
    min-width: 0;
  }

  &__type {
    margin-top: 4px;
    font-size: var(--fs-xs);
    color: var(--muted);

    // Terracotta foncé : le terracotta de la maquette n'atteint pas le contraste AA sur blanc
    span {
      font-weight: 500;
      color: var(--terracotta-ink);
    }
  }
}

// Tablette : le bloc prend toute la largeur, ressources sur 2 colonnes
@media (min-width: 720px) and (max-width: 1079px) {
  .home-resources {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
