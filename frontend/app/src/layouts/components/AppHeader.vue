<script setup lang="ts">
// En-tête (fiche SiteHeader) : logo, navigation principale, menu du compte.
// Sous 720 px, la navigation passe dans le tiroir (bouton menu) et la barre d'onglets.
import { useRoute } from 'vue-router';

import AppLogo from '@/components/brand/AppLogo.vue';
import { isNavigationItemActive, MAIN_NAVIGATION } from '@/router/navigation';
import AccountMenu from './AccountMenu.vue';

defineEmits<{ 'open-drawer': [] }>();

const route = useRoute();
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

      <router-link :to="{ name: 'home' }" class="app-header__home" aria-label="RIMEF, accueil">
        <AppLogo decorative compact :height="40" :compact-height="34" />
      </router-link>

      <nav class="app-header__nav gt-xs" aria-label="Navigation principale">
        <q-btn
          v-for="item in MAIN_NAVIGATION"
          :key="item.routeName"
          flat
          no-caps
          :ripple="false"
          :to="{ name: item.routeName }"
          :label="item.label"
          class="app-header__link"
          :class="{ 'app-header__link--active': isNavigationItemActive(item, route.name) }"
          :aria-current="isNavigationItemActive(item, route.name) ? 'page' : undefined"
        />
      </nav>

      <div class="app-header__tools">
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
    display: flex;
    gap: var(--s-4);
    align-self: stretch;
  }

  &__link {
    position: relative;
    align-self: stretch;
    padding: 0 var(--s-2);
    border-radius: 0;
    font-size: var(--fs-sm);
    font-weight: 500;
    color: var(--ink-2);

    &:hover {
      color: var(--ink);
    }

    // Lien courant : pétrole, graisse 600, filet de 2 px au ras de la bordure
    &--active {
      color: var(--petrol);
      font-weight: 600;

      &::after {
        content: '';
        position: absolute;
        right: 0;
        bottom: -1px;
        left: 0;
        height: 2px;
        background: var(--petrol);
      }
    }

    :deep(.q-focus-helper) {
      display: none;
    }
  }

  &__tools {
    display: flex;
    align-items: center;
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
