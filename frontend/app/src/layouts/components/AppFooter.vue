<script setup lang="ts">
// Pied de page (fiche SiteFooter) : pages d'information à gauche, mention de copyright à
// droite sur la même ligne, ornement feuilles blanches en filigrane à droite (décoratif).
import { FOOTER_NAVIGATION } from '@/router/navigation';

const YEAR = new Date().getFullYear();
</script>

<template>
  <footer class="app-footer">
    <img src="/rimef-feuilles-blanc.svg" alt="" aria-hidden="true" class="app-footer__ornament" />

    <div class="container app-footer__inner">
      <nav class="app-footer__nav" aria-label="Informations">
        <ul class="app-footer__list">
          <li v-for="item in FOOTER_NAVIGATION" :key="item.routeName" class="app-footer__item">
            <router-link :to="{ name: item.routeName }" class="app-footer__link">
              {{ item.label }}
            </router-link>
          </li>
        </ul>
      </nav>

      <p class="app-footer__copyright">
        © {{ YEAR }} RIMeF · Réseau International des Médiatrices Francophones
      </p>
    </div>
  </footer>
</template>

<style lang="scss" scoped>
.app-footer {
  position: relative;
  overflow: hidden;
  background: var(--petrol-dark);
  color: var(--on-dark);

  &__inner {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--s-2) var(--s-8);
    padding-block: var(--s-5);
  }

  // Filigrane ancré dans le coin droit, rogné par le pied de page
  &__ornament {
    position: absolute;
    right: max(var(--gutter), calc((100% - var(--container)) / 2 + var(--gutter)));
    bottom: -48px;
    height: 190px;
    opacity: 0.12;
    pointer-events: none;
    user-select: none;
  }

  // Point médian entre les liens, hors des liens et masqué aux lecteurs d'écran.
  // Le point qui tomberait en début de ligne (retour à la ligne) est rogné par
  // overflow: hidden grâce à la marge négative de la liste.
  &__nav {
    flex-shrink: 0; // les liens restent sur une ligne ; seule la mention passe à la ligne
    overflow: hidden;
  }

  &__list {
    display: flex;
    flex-wrap: wrap;
    margin: 0 0 0 calc(-1 * var(--s-6));
    padding: 4px 0;
    list-style: none;
  }

  &__item {
    position: relative;
    padding-left: var(--s-6);

    &::before {
      content: '·' / '';
      position: absolute;
      top: 50%;
      left: 0;
      width: var(--s-6);
      color: rgba(245, 241, 232, 0.5);
      text-align: center;
      transform: translateY(-50%);
    }
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

  // À droite des liens, mais arrêtée avant les feuilles en filigrane (≈ 135 px de large)
  &__copyright {
    margin-right: 160px;
    font-size: var(--fs-xs);
    color: rgba(245, 241, 232, 0.72);
    text-align: right;
  }
}

@media (max-width: 719px) {
  .app-footer__inner {
    flex-direction: column;
    align-items: flex-start;
    padding-block: var(--s-5) var(--s-6);
  }

  .app-footer__nav {
    flex-shrink: 1;
  }

  .app-footer__copyright {
    margin-right: 0;
    text-align: left;
  }

  .app-footer__ornament {
    right: var(--s-2);
    bottom: -36px;
    height: 150px;
  }
}
</style>
