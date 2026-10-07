<script setup lang="ts">
import AppLogo from '@/components/brand/AppLogo.vue';
</script>

<template>
  <q-layout view="hHh lpR fff">
    <q-header class="main-header">
      <div class="container main-header__inner">
        <router-link :to="{ name: 'home' }" class="main-header__home" aria-label="RIMEF, accueil">
          <AppLogo />
        </router-link>
      </div>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<style lang="scss" scoped>
.main-header {
  height: var(--header-h);
  background: rgba(252, 250, 246, 0.94);
  border-bottom: 1px solid var(--border);
  color: var(--ink);
  backdrop-filter: saturate(1.1) blur(6px);

  &__inner {
    display: flex;
    align-items: center;
    height: 100%;
  }
}

.main-header__home {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  text-decoration: none;
}
</style>
