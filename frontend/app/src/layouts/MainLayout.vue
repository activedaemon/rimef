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

    <q-page-container>
      <router-view />
      <AppFooter />
    </q-page-container>

    <AppTabBar v-if="$q.screen.lt.sm" />
  </q-layout>
</template>
