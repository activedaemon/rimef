<template>
  <q-layout view="hHh lpR fff">
    <q-header class="supervisor-header">
      <div class="container supervisor-header__inner">
        <router-link
          :to="{ name: 'dashboard' }"
          class="logo"
          aria-label="RIMeF Supervision, tableau de bord"
        >
          <img src="/favicon.svg" alt="" width="26" height="26" />
          <span class="logo__word">RIMeF</span>
          <span class="logo__space">Supervision</span>
        </router-link>
      </div>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<style lang="scss" scoped>
// Charte RIMeF en version sobre : fond ivoire, en-tête pétrole sombre, sans ornement.
.supervisor-header {
  height: 56px;
  background: var(--petrol-dark);
  color: var(--on-dark);

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
  color: var(--on-dark);
  text-decoration: none;

  &__word {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.4rem;
    line-height: 1;
  }

  &__space {
    padding-left: 10px;
    border-left: 1px solid rgba(245, 241, 232, 0.3);
    font-size: var(--fs-sm);
    font-weight: 500;
    letter-spacing: 0.04em;
  }
}
</style>
