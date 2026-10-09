<script setup lang="ts">
// En-tête (fiche SiteHeader) : logo, navigation principale, menu du compte.
// Sous 720 px, la navigation passe dans le tiroir (bouton menu) et la barre d'onglets.
// Navigation en q-tabs / q-route-tab (onde, soulignement animé) : choix assumé, les
// lecteurs d'écran annoncent des « onglets » ; la zone reste une <nav> libellée.
import AppLogo from '@/components/brand/AppLogo.vue';
import { MAIN_NAVIGATION } from '@/router/navigation';
import AccountMenu from './AccountMenu.vue';
import HeaderSearch from './HeaderSearch.vue';

defineEmits<{ 'open-drawer': [] }>();
</script>

<template>
  <q-header class="app-header">
    <div class="container app-header__inner">
      <q-btn
        flat
        round
        icon="menu-2"
        class="app-header__menu lt-sm"
        aria-label="Ouvrir le menu"
        @click="$emit('open-drawer')"
      />

      <router-link :to="{ name: 'home' }" class="app-header__home" aria-label="RIMeF, accueil">
        <AppLogo decorative compact :height="40" :compact-height="34" />
      </router-link>

      <nav class="app-header__nav gt-xs" aria-label="Navigation principale">
        <q-tabs
          no-caps
          inline-label
          align="left"
          active-color="primary"
          indicator-color="primary"
          class="app-header__tabs"
        >
          <q-route-tab
            v-for="item in MAIN_NAVIGATION"
            :key="item.routeName"
            :to="{ name: item.routeName }"
            :exact="item.routeName === 'home'"
            :icon="item.icon"
            :label="item.label"
            class="app-header__tab"
          />
        </q-tabs>
      </nav>

      <div class="app-header__tools">
        <HeaderSearch />
        <AccountMenu />
      </div>
    </div>
  </q-header>
</template>

<style lang="scss" scoped>
.app-header {
  height: var(--header-h);
  background: rgba(252, 250, 246, 0.94);
  border-bottom: 1px solid var(--border);
  color: var(--ink);
  backdrop-filter: saturate(1.1) blur(6px);

  &__inner {
    display: flex;
    align-items: center;
    gap: var(--s-10);
    height: 100%;
  }

  &__home {
    display: inline-flex;
    align-items: center;
    min-height: 44px;
  }

  &__nav {
    align-self: stretch;
  }

  &__tabs {
    height: 100%;
    color: var(--ink-2);

    // Soulignement de 2 px au ras du filet du bandeau (fiche SiteHeader)
    :deep(.q-tab__indicator) {
      height: 2px;
    }
  }

  &__tab {
    min-height: 100%;
    padding: 0 var(--s-4);
    font-size: var(--fs-sm);
    font-weight: 500;

    // Icône à gauche du libellé (inline-label)
    :deep(.q-tab__icon) {
      width: 18px;
      height: 18px;
      font-size: 18px;
    }

    :deep(.q-tab__label) {
      padding-left: var(--s-2);
      font-size: var(--fs-sm);
      line-height: 1;
    }

    &:hover {
      color: var(--ink);
    }

    &.q-tab--active {
      font-weight: 600;
    }
  }

  &__tools {
    display: flex;
    align-items: center;
    gap: var(--s-4);
    margin-left: auto;
  }
}

@media (max-width: 1079px) {
  .app-header__inner {
    gap: var(--s-6);
  }
}

@media (max-width: 719px) {
  .app-header__inner {
    gap: var(--s-2);
  }

  .app-header__menu {
    margin-left: calc(-1 * var(--s-2));
  }
}
</style>
