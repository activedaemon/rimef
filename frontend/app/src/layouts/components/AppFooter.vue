<script setup lang="ts">
// Pied de page (fiche SiteFooter) : logo, navigation, devise, mention de copyright.
// Langue, réseaux sociaux et pages d'information viendront avec leurs contenus.
import AppLogo from '@/components/brand/AppLogo.vue';
import { MAIN_NAVIGATION } from '@/router/navigation';

const YEAR = new Date().getFullYear();
</script>

<template>
  <footer class="app-footer">
    <div class="container app-footer__inner">
      <router-link :to="{ name: 'home' }" class="app-footer__home" aria-label="RIMEF, accueil">
        <AppLogo on-dark decorative :height="56" :compact-height="0" />
      </router-link>

      <nav class="app-footer__nav" aria-label="Pied de page">
        <router-link
          v-for="item in MAIN_NAVIGATION"
          :key="item.routeName"
          :to="{ name: item.routeName }"
          class="app-footer__link"
        >
          {{ item.label }}
        </router-link>
      </nav>

      <p class="app-footer__motto">Des femmes en dialogue pour des sociétés plus pacifiques.</p>
    </div>

    <div class="app-footer__bottom">
      <div class="container">
        © {{ YEAR }} RIMEF · Réseau International des Médiatrices Francophones
      </div>
    </div>
  </footer>
</template>

<style lang="scss" scoped>
.app-footer {
  background: var(--petrol-dark);
  color: var(--on-dark);

  &__inner {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: var(--s-10);
    padding-block: var(--s-10);
  }

  &__home {
    display: inline-flex;
  }

  &__nav {
    display: flex;
    flex-wrap: wrap;
    gap: var(--s-2) var(--s-6);
  }

  &__link {
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    font-size: var(--fs-sm);
    color: rgba(245, 241, 232, 0.72);
    text-decoration: none;

    &:hover,
    &:focus-visible {
      color: var(--on-dark);
    }
  }

  &__motto {
    max-width: 26ch;
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.05rem;
    font-style: italic;
    line-height: 1.35;
  }

  &__bottom {
    padding-block: var(--s-4);
    border-top: 1px solid rgba(245, 241, 232, 0.14);
    font-size: var(--fs-xs);
    color: rgba(245, 241, 232, 0.72);
  }
}

@media (max-width: 1079px) {
  .app-footer__inner {
    grid-template-columns: auto 1fr;
  }

  .app-footer__motto {
    grid-column: 1 / -1;
    grid-row: 1;
    max-width: none;
  }
}

@media (max-width: 719px) {
  .app-footer__inner {
    grid-template-columns: 1fr;
    gap: var(--s-6);
    padding-block: var(--s-8);
  }
}
</style>
