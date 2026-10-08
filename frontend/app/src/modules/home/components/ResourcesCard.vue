<script setup lang="ts">
// Ressources à découvrir (maquette Accueil) : vignette, titre, type et date.
import type { ResourceSummary } from '../services/home-feed';

defineProps<{ resources: ResourceSummary[] }>();
</script>

<template>
  <q-card flat class="rimef-card home-block" tag="section" aria-labelledby="resources-title">
    <div class="card-head">
      <h2 id="resources-title" class="card-title">Ressources à découvrir</h2>
      <router-link :to="{ name: 'resources' }" class="link-more">Voir tout</router-link>
    </div>
    <ul class="home-block__list">
      <li v-for="resource in resources" :key="resource.id" class="resource">
        <!-- Vignette neutre en attendant les visuels des ressources -->
        <span class="resource__thumb" aria-hidden="true">
          <q-icon :name="resource.icon" size="24px" />
        </span>
        <div>
          <h3 class="home-block__item-title">
            <router-link :to="{ name: 'resources' }">{{ resource.title }}</router-link>
          </h3>
          <p class="resource__type">
            <span>{{ resource.type }}</span> · {{ resource.detail }}
          </p>
        </div>
      </li>
    </ul>
  </q-card>
</template>

<style lang="scss" scoped>
@use './home-block';

.resource {
  display: flex;
  align-items: center;
  gap: 14px;

  &__thumb {
    display: grid;
    flex: none;
    place-items: center;
    width: 62px;
    height: 62px;
    border-radius: var(--radius-xs);
    background: var(--terracotta-light);
    color: var(--terracotta-ink);
  }

  &__type {
    margin-top: 4px;
    font-size: var(--fs-xs);
    color: var(--muted);

    span {
      font-weight: 500;
      color: var(--terracotta-ink);
    }
  }
}
</style>
