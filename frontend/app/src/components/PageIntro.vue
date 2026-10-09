<script setup lang="ts">
// Intro de page : sur-titre optionnel, titre serif, phrase d'accompagnement (58 caractères max),
// regroupés dans un bloc aligné à gauche. Emplacements facultatifs :
// - « below » : sous le texte, dans la même colonne (ex. filtres de l'annuaire) ;
// - « aside » : à droite, sur toute la hauteur du texte et de « below » (ex. citation).
// Sur téléphone, l'ordre devient : texte, aside, below.
defineProps<{ title: string; lead?: string; eyebrow?: string }>();
</script>

<template>
  <header class="page-intro" :class="{ 'page-intro--with-aside': $slots.aside }">
    <div class="page-intro__text">
      <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
      <h1 class="page-intro__title">{{ title }}</h1>
      <p v-if="lead" class="page-intro__lead">{{ lead }}</p>
      <slot />
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
  padding-block: var(--s-10) var(--s-8);

  &--with-aside {
    grid-template-areas:
      'text aside'
      'below aside';
    grid-template-columns: minmax(0, 1fr) 360px;
    grid-template-rows: auto 1fr;
    column-gap: var(--s-8);
  }

  &__text {
    display: grid;
    grid-area: text;
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
    padding-block: var(--s-8) var(--s-6);

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
