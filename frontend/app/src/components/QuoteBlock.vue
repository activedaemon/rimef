<script setup lang="ts">
// Citation éditoriale (fiche Quote) : phrase en italique serif, signature, feuilles en coin.
// - variant « banner » : pavé pleine largeur (pages intérieures)
// - variant « card » : carte compacte, texte étroit centré verticalement (accueil)
// - tone « sand » : aplat sable des grands blocs éditoriaux ; « ivory » : fond ivory
import { LEAF_PATH, leafTransform } from '@/lib/leaf';

withDefaults(
  defineProps<{
    text: string;
    cite?: string;
    variant?: 'banner' | 'card';
    tone?: 'sand' | 'ivory';
  }>(),
  { cite: 'RIMeF', variant: 'banner', tone: 'sand' }
);
</script>

<template>
  <q-card
    flat
    tag="figure"
    class="quote-block"
    :class="[`quote-block--${variant}`, `quote-block--${tone}`]"
  >
    <blockquote class="quote-block__text">« {{ text }} »</blockquote>
    <figcaption class="quote-block__cite">— {{ cite }}</figcaption>
    <svg class="quote-block__leaf" viewBox="0 0 150 170" aria-hidden="true">
      <path
        :d="LEAF_PATH"
        :transform="leafTransform(118, 166, -128, 130, 44)"
        fill="#C86F55"
        opacity="0.85"
      />
      <path
        :d="LEAF_PATH"
        :transform="leafTransform(120, 164, -72, 112, 38)"
        fill="#8FA89C"
        opacity="0.85"
      />
      <path :d="LEAF_PATH" :transform="leafTransform(116, 168, -168, 70, 24)" fill="#E9DCC4" />
    </svg>
  </q-card>
</template>

<style lang="scss" scoped>
.quote-block.q-card {
  position: relative;
  display: flex;
  flex-direction: column;
  margin: 0;
  overflow: hidden;
  border: 1px solid var(--border);
  box-shadow: none;
}

.quote-block {
  &--sand.q-card {
    border-color: var(--sand-line);
    background: var(--sand);
  }

  &--ivory.q-card {
    background: var(--ivory);
  }

  &__text {
    position: relative;
    z-index: 1;
    margin: 0;
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: clamp(1.6rem, 1.3rem + 1vw, 2rem);
    font-style: italic;
    line-height: 1.22;
    color: var(--ink);
    text-wrap: balance;
  }

  &__cite {
    position: relative;
    z-index: 1;
    font-size: var(--fs-xs);
    letter-spacing: 0.08em;
    color: var(--ink-2);
  }

  &__leaf {
    position: absolute;
    right: -8px;
    bottom: -6px;
  }

  // Pavé pleine largeur
  &--banner.q-card {
    margin-bottom: var(--s-8);
    padding: var(--s-8) 180px var(--s-8) var(--s-10);
    border-radius: var(--radius-md);
  }

  &--banner &__text {
    max-width: 30ch;
  }

  &--banner &__cite {
    margin-top: var(--s-5);
  }

  &--banner &__leaf {
    width: 120px;
    height: 136px;
  }

  // Carte compacte de l'accueil
  &--card.q-card {
    justify-content: center;
    padding: 56px 36px;
    border-radius: var(--radius-sm);
  }

  &--card &__text {
    max-width: 13ch;
  }

  &--card &__cite {
    margin-top: 20px;
  }

  &--card &__leaf {
    width: 150px;
    height: 170px;
  }
}

@media (max-width: 1079px) {
  .quote-block {
    &--card.q-card {
      padding: 36px;
    }

    &--card .quote-block__text {
      max-width: 24ch;
    }

    &--card .quote-block__leaf {
      width: 110px;
      height: 130px;
    }
  }
}

@media (max-width: 719px) {
  .quote-block {
    &--banner.q-card {
      margin-bottom: var(--s-6);
      padding: var(--s-6) var(--s-5) var(--s-10);
    }

    &--card.q-card {
      padding: 30px 24px 40px;
    }

    &__leaf,
    &--banner .quote-block__leaf,
    &--card .quote-block__leaf {
      width: 84px;
      height: 100px;
    }
  }
}
</style>
