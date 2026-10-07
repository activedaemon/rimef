<script setup lang="ts">
// Barre d'onglets mobile (fiche TabBar), sous 720 px : Accueil, Réseau, Agenda, Ressources, Profil.
import { useRoute } from 'vue-router';

import { isNavigationItemActive, TAB_BAR_NAVIGATION } from '@/router/navigation';

const route = useRoute();
</script>

<template>
  <q-footer class="app-tab-bar">
    <nav class="app-tab-bar__nav" aria-label="Navigation mobile">
      <q-btn
        v-for="item in TAB_BAR_NAVIGATION"
        :key="item.routeName"
        flat
        stack
        no-caps
        :ripple="false"
        :to="{ name: item.routeName }"
        :icon="item.icon"
        :label="item.label"
        class="app-tab-bar__tab"
        :class="{ 'app-tab-bar__tab--active': isNavigationItemActive(item, route.name) }"
        :aria-current="isNavigationItemActive(item, route.name) ? 'page' : undefined"
      />
    </nav>
  </q-footer>
</template>

<style lang="scss" scoped>
.app-tab-bar {
  padding-bottom: env(safe-area-inset-bottom, 0px);
  background: rgba(252, 250, 246, 0.97);
  border-top: 1px solid var(--border);
  color: var(--muted);

  &__nav {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
  }

  &__tab {
    min-height: 56px;
    padding: 6px 0;
    border-radius: 0;
    font-size: 11px;
    color: var(--muted);

    :deep(.q-icon) {
      margin-bottom: 3px;
      font-size: 21px;
    }

    :deep(.q-focus-helper) {
      display: none;
    }

    &--active {
      color: var(--petrol);
      font-weight: 600;
    }
  }
}
</style>
