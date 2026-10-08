<script setup lang="ts">
// Bandeau éditorial (maquette Accueil) : valeurs du réseau en italique serif sur pétrole sombre,
// invitation à compléter son profil (la maquette proposait « Rejoindre le réseau »).
import { LEAF_PATH, leafTransform } from '@/lib/leaf';
</script>

<template>
  <aside class="editorial-band" aria-label="Communauté RIMeF">
    <svg
      class="editorial-band__leaf editorial-band__leaf--left"
      viewBox="0 0 110 120"
      aria-hidden="true"
    >
      <path :d="LEAF_PATH" :transform="leafTransform(10, 118, -52, 120, 42)" fill="#8FA89C" />
    </svg>
    <p class="editorial-band__text">Solidarité mutuelle et mentorat intergénérationnel.</p>
    <q-btn
      unelevated
      no-caps
      label="Compléter mon profil"
      :to="{ name: 'profile-edit' }"
      class="editorial-band__button"
    />
    <svg
      class="editorial-band__leaf editorial-band__leaf--right"
      viewBox="0 0 120 140"
      aria-hidden="true"
    >
      <path :d="LEAF_PATH" :transform="leafTransform(120, 12, 145, 110, 38)" fill="#C86F55" />
      <path
        :d="LEAF_PATH"
        :transform="leafTransform(120, 16, 170, 80, 28)"
        fill="#8A5A46"
        opacity="0.8"
      />
    </svg>
  </aside>
</template>

<style lang="scss" scoped>
.editorial-band {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 56px;
  padding: 38px 48px;
  overflow: hidden;
  border-radius: var(--radius-md);
  background: var(--petrol-dark);
  color: var(--on-dark);

  &__text {
    position: relative;
    z-index: 1;
    max-width: 24ch;
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: clamp(1.35rem, 1.1rem + 0.8vw, 1.7rem);
    font-style: italic;
    line-height: 1.25;
    text-align: center;
    text-wrap: balance;
  }

  &__button {
    position: relative;
    z-index: 1;
    background: var(--paper);
    color: var(--petrol-dark);

    &:hover {
      background: var(--ivory);
    }
  }

  &__leaf {
    position: absolute;

    &--left {
      bottom: -34px;
      left: 28px;
      width: 110px;
      height: 120px;
      opacity: 0.55;
    }

    &--right {
      top: -18px;
      right: -14px;
      width: 120px;
      height: 140px;
    }
  }
}

@media (max-width: 719px) {
  .editorial-band {
    flex-direction: column;
    gap: 22px;
    padding: 34px 24px 38px;

    &__button {
      width: 100%;
      max-width: 280px;
    }
  }
}
</style>
