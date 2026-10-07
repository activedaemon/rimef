<script setup lang="ts">
// Logo RIMEF : quatre feuilles, RIMEF et la mention « Réseau international des
// médiatrices francophones » (public/logo-rimef.svg, texte vectorisé).
// Sous 720 px, la version compacte sans mention (illisible à cette taille) prend le relais.
withDefaults(
  defineProps<{
    /** Hauteur en px (version complète). */
    height?: number;
    /** Hauteur en px de la version compacte, sous 720 px ; 0 = garder la version complète. */
    compactHeight?: number;
    /** Logo dans un lien déjà libellé : texte alternatif vide. */
    decorative?: boolean;
  }>(),
  { height: 52, compactHeight: 36, decorative: false }
);

const LABEL = 'RIMEF — Réseau international des médiatrices francophones';
</script>

<template>
  <picture
    class="app-logo"
    :style="{
      '--logo-height': `${height}px`,
      '--logo-compact-height': `${compactHeight || height}px`,
    }"
  >
    <source v-if="compactHeight" media="(max-width: 720px)" srcset="/logo-rimef-compact.svg" />
    <img src="/logo-rimef.svg" :alt="decorative ? '' : LABEL" class="app-logo__img" />
  </picture>
</template>

<style lang="scss" scoped>
.app-logo {
  display: inline-flex;

  &__img {
    display: block;
    width: auto;
    height: var(--logo-height);
  }
}

@media (max-width: 720px) {
  .app-logo__img {
    height: var(--logo-compact-height);
  }
}
</style>
