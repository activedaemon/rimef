<template>
  <q-layout view="hHh lpR fff">
    <q-header class="main-header">
      <div class="container main-header__inner">
        <router-link :to="{ name: 'home' }" class="logo" aria-label="RIMEF, accueil">
          <img src="/favicon.svg" alt="" width="30" height="30" />
          <span class="logo__word">RIMEF</span>
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

.logo {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-height: 44px;
  color: var(--ink);
  text-decoration: none;

  &__word {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.6rem;
    line-height: 1;
    letter-spacing: 0.01em;
  }
}
</style>
