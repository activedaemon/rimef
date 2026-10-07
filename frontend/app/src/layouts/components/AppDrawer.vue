<script setup lang="ts">
// Tiroir mobile (bouton menu de l'en-tête) : navigation en Instrument Serif, puis compte.
import { useRoute } from 'vue-router';

import { useLogout } from '@/composables/useLogout';
import { isNavigationItemActive, MAIN_NAVIGATION } from '@/router/navigation';

const open = defineModel<boolean>({ required: true });

const route = useRoute();
const { logout, loggingOut } = useLogout();
</script>

<template>
  <q-drawer
    v-model="open"
    side="left"
    overlay
    behavior="mobile"
    :width="300"
    class="app-drawer"
    aria-label="Menu"
  >
    <div class="app-drawer__inner">
      <q-btn
        v-close-popup
        flat
        round
        icon="x"
        aria-label="Fermer le menu"
        class="app-drawer__close"
        @click="open = false"
      />

      <nav aria-label="Navigation principale">
        <q-list>
          <q-item
            v-for="item in MAIN_NAVIGATION"
            :key="item.routeName"
            clickable
            :to="{ name: item.routeName }"
            class="app-drawer__link"
            :class="{ 'app-drawer__link--active': isNavigationItemActive(item, route.name) }"
            :aria-current="isNavigationItemActive(item, route.name) ? 'page' : undefined"
            @click="open = false"
          >
            <q-item-section>{{ item.label }}</q-item-section>
          </q-item>
        </q-list>
      </nav>

      <q-list class="app-drawer__account">
        <q-item clickable :to="{ name: 'profile' }" class="app-drawer__sub" @click="open = false">
          <q-item-section avatar><q-icon name="user" size="18px" /></q-item-section>
          <q-item-section>Mon profil</q-item-section>
        </q-item>
        <q-item clickable class="app-drawer__sub" :disable="loggingOut" @click="logout">
          <q-item-section avatar><q-icon name="logout" size="18px" /></q-item-section>
          <q-item-section>Se déconnecter</q-item-section>
        </q-item>
      </q-list>
    </div>
  </q-drawer>
</template>

<style lang="scss" scoped>
.app-drawer {
  &__inner {
    display: flex;
    flex-direction: column;
    gap: var(--s-1);
    height: 100%;
    padding: calc(var(--s-5) + env(safe-area-inset-top, 0px)) var(--s-6) var(--s-6);
    background: var(--paper);
  }

  &__close {
    align-self: flex-end;
  }

  &__link {
    min-height: 56px;
    padding: 0;
    border-bottom: 1px solid var(--border);
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.6rem;
    color: var(--ink);

    &--active {
      color: var(--petrol);
    }
  }

  &__account {
    margin-top: var(--s-5);
  }

  &__sub {
    min-height: 44px;
    padding: 0;
    color: var(--ink-2);

    .q-item__section--avatar {
      min-width: 32px;
      color: var(--muted);
    }
  }
}
</style>
