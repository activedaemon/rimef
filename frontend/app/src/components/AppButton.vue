<script setup lang="ts">
// Bouton de la charte (fiche Button), sur q-btn : toutes ses props passent telles quelles
// (label, icon, to, type, loading…). Hauteur 44 px (zone tactile), texte 13 px graisse 500.
// - primary : action principale de la zone (pétrole)
// - accent : action d'appel forte, ex. « Contacter » (terracotta)
// - outline : action secondaire (fond paper, contour pétrole)
// - light : sur fond sombre ou photo (fond paper, sans bordure)
// - quiet : action discrète, ex. « Annuler » (fond paper, filet)
// - size « sm » : 34 px, à réserver aux zones hors mobile (menus déroulants)
// Sans onde au clic : la charte ne prévoit que des changements de couleur.
withDefaults(
  defineProps<{
    variant?: 'primary' | 'accent' | 'outline' | 'light' | 'quiet';
    size?: 'md' | 'sm';
  }>(),
  { variant: 'primary', size: 'md' }
);
</script>

<template>
  <q-btn
    unelevated
    no-caps
    :ripple="false"
    class="app-btn"
    :class="[`app-btn--${variant}`, `app-btn--${size}`]"
  >
    <slot />
  </q-btn>
</template>

<style lang="scss" scoped>
.app-btn {
  min-height: 44px;
  padding: 0 22px;
  border: 1px solid transparent;
  border-radius: var(--radius-xs);
  font-size: var(--fs-sm);
  font-weight: 500;
  letter-spacing: 0.005em;
  transition:
    background 0.15s,
    border-color 0.15s,
    color 0.15s;

  // Survols de la charte à la place du voile gris de Quasar
  :deep(.q-focus-helper) {
    display: none;
  }

  :deep(.q-icon) {
    font-size: 16px;
  }

  :deep(.q-icon.on-left) {
    margin-right: 8px;
  }

  &--primary {
    background: var(--petrol);
    color: var(--paper);

    &:hover {
      background: var(--petrol-dark);
    }
  }

  // Terracotta foncé : le terracotta de la maquette (#C86F55) n'atteint pas le contraste AA
  // avec un texte blanc de 13 px
  &--accent {
    background: var(--terracotta-ink);
    color: var(--paper);

    &:hover {
      background: #86412e;
    }
  }

  &--outline {
    border-color: var(--petrol);
    background: var(--paper);
    color: var(--petrol);

    &:hover {
      background: var(--petrol-light);
    }
  }

  &--light {
    background: var(--paper);
    color: var(--petrol-dark);

    &:hover {
      background: var(--ivory);
    }
  }

  &--quiet {
    border-color: var(--border);
    background: var(--paper);
    color: var(--ink-2);

    &:hover {
      border-color: var(--border-hover);
      color: var(--ink);
    }
  }

  // Plus spécifique que la zone tactile globale (.q-btn:not(.q-btn--dense))
  &.app-btn--sm {
    min-height: 34px;
    padding: 0 14px;
    font-size: var(--fs-xs);
  }
}
</style>
