<script setup lang="ts">
// Intro de page : sur-titre optionnel, titre serif, phrase d'accompagnement (58 caractères max),
// regroupés dans un bloc aligné à gauche. Emplacements facultatifs :
// - « below » : sous le texte, dans la même colonne (ex. filtres de l'annuaire) ;
// - « aside » : à droite, sur toute la hauteur du texte et de « below » (ex. citation).
// Sur téléphone, l'ordre devient : texte, aside, below.
// leaf : ornement de feuilles (terracotta et sauge) juste après le texte (maquettes Agenda, Messages).
import { LEAF_PATH, leafTransform } from '@/lib/leaf';

defineProps<{ title: string; lead?: string; eyebrow?: string; leaf?: boolean }>();
</script>

<template>
  <header class="page-intro" :class="{ 'page-intro--with-aside': $slots.aside }">
    <div class="page-intro__head">
      <div class="page-intro__text">
        <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
        <h1 class="page-intro__title">{{ title }}</h1>
        <p v-if="lead" class="page-intro__lead">{{ lead }}</p>
        <slot />
      </div>
      <svg v-if="leaf" class="page-intro__leaf" viewBox="0 0 120 96" aria-hidden="true">
        <path
          :d="LEAF_PATH"
          :transform="leafTransform(112, 90, -150, 92, 32)"
          fill="var(--terracotta)"
          opacity=".85"
        />
        <path
          :d="LEAF_PATH"
          :transform="leafTransform(114, 92, -112, 70, 25)"
          fill="var(--leaf-sage)"
          opacity=".85"
        />
      </svg>
    </div>
    <div v-if="$slots.aside" class="page-intro__aside">
      <slot name="aside" />
    </div>
    <div v-if="$slots.below" class="page-intro__below">
      <slot name="below" />
    </div>
  </header>
</template>

<style lang="scss" scoped>
.page-intro {
  display: grid;
  grid-template-areas:
    'text'
    'below';
  row-gap: var(--s-5);
  padding-block: var(--s-8) var(--s-6);

  &--with-aside {
    grid-template-areas:
      'text aside'
      'below aside';
    grid-template-columns: minmax(0, 1fr) 360px;
    grid-template-rows: auto 1fr;
    column-gap: var(--s-8);
  }

  // Texte et, s'il y a lieu, l'ornement de feuilles centré à sa droite
  &__head {
    display: flex;
    grid-area: text;
    align-items: center;
    gap: 16px;
    min-width: 0;
  }

  &__leaf {
    flex: none;
    width: 120px;
    height: 96px;
    opacity: 0.9;
    pointer-events: none;
  }

  &__text {
    display: grid;
    min-width: 0;
    align-content: start;
    justify-items: start;
    gap: var(--s-3);
  }

  &__title {
    line-height: 1.02;
    letter-spacing: -0.012em;
  }

  &__lead {
    max-width: 58ch;
    font-size: var(--fs-lead);
    color: var(--ink-2);
  }

  &__aside {
    display: flex;
    grid-area: aside;

    > * {
      flex: 1;
    }
  }

  &__below {
    grid-area: below;
    align-self: end;
    min-width: 0;
  }
}

@media (max-width: 1079px) {
  .page-intro--with-aside {
    grid-template-columns: minmax(0, 1fr) 300px;
    column-gap: var(--s-6);
  }
}

@media (max-width: 719px) {
  .page-intro {
    padding-block: var(--s-6) var(--s-4);

    &__leaf {
      width: 72px;
      height: 58px;
    }

    &--with-aside {
      grid-template-areas:
        'text'
        'aside'
        'below';
      grid-template-columns: minmax(0, 1fr);
      grid-template-rows: auto;
    }
  }
}
</style>
