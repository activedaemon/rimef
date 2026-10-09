<script setup lang="ts">
import { ref } from 'vue';

import AppDrawer from './components/AppDrawer.vue';
import AppFooter from './components/AppFooter.vue';
import AppHeader from './components/AppHeader.vue';
import AppTabBar from './components/AppTabBar.vue';

const drawerOpen = ref(false);
</script>

<template>
  <q-layout view="hHh lpR fFf">
    <AppHeader @open-drawer="drawerOpen = true" />
    <AppDrawer v-if="$q.screen.lt.sm" v-model="drawerOpen" />

    <q-page-container class="main-layout__container">
      <router-view />
      <AppFooter />
    </q-page-container>

    <AppTabBar v-if="$q.screen.lt.sm" />
  </q-layout>
</template>

<style lang="scss" scoped>
// Pied de page calé en bas des pages courtes : la page occupe la hauteur restante.
// q-page impose une hauteur minimale en ligne (fenêtre - en-tête), d'où le !important.
.main-layout__container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;

  > :deep(.q-page) {
    flex: 1 0 auto;
    min-height: 0 !important;
  }
}
</style>
