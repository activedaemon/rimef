<script setup lang="ts">
// Barre d'onglets mobile (fiche TabBar), sous 720 px : Accueil, Réseau, Agenda, Ressources, Profil.
// q-tabs / q-route-tab (onde, indicateur animé) : choix assumé, les lecteurs d'écran
// annoncent des « onglets » ; la zone reste une <nav> libellée.
import { TAB_BAR_NAVIGATION } from '@/router/navigation';
</script>

<template>
  <q-footer class="app-tab-bar">
    <nav aria-label="Navigation mobile">
      <q-tabs
        no-caps
        switch-indicator
        active-color="primary"
        indicator-color="primary"
        class="app-tab-bar__tabs"
      >
        <q-route-tab
          v-for="item in TAB_BAR_NAVIGATION"
          :key="item.routeName"
          :to="{ name: item.routeName }"
          :exact="item.routeName === 'home'"
          :icon="item.icon"
          :label="item.label"
          class="app-tab-bar__tab"
        />
      </q-tabs>
    </nav>
  </q-footer>
</template>

<style lang="scss" scoped>
.app-tab-bar {
  padding-bottom: env(safe-area-inset-bottom, 0px);
  background: rgba(252, 250, 246, 0.97);
  border-top: 1px solid var(--border);
  color: var(--muted);

  &__tabs {
    :deep(.q-tabs__content) {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
    }

    :deep(.q-tab__indicator) {
      height: 2px;
    }
  }

  &__tab {
    min-height: 56px;
    padding: 6px 0;

    :deep(.q-tab__icon) {
      width: 21px;
      height: 21px;
      font-size: 21px;
    }

    :deep(.q-tab__label) {
      margin-top: 3px;
      font-size: 11px;
      line-height: 1.2;
    }

    &.q-tab--active :deep(.q-tab__label) {
      font-weight: 600;
    }
  }
}
</style>
