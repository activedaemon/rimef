<script setup lang="ts">
// Logo RIMeF (texte vectorisé) :
// - complet : feuilles, RIMeF et la mention (public/logo-rimef.svg), remplacé sous 720 px
//   par la version compacte, la mention y étant illisible ;
// - compact : feuilles centrées sur RIMeF, sans mention (bandeau du haut).
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    /** Hauteur en px (version complète). */
    height?: number;
    /** Hauteur en px de la version compacte, sous 720 px ; 0 = garder la version complète. */
    compactHeight?: number;
    /** Logo dans un lien déjà libellé : texte alternatif vide. */
    decorative?: boolean;
    /** Version compacte (feuilles + RIMeF, sans la mention) à toutes les tailles. */
    compact?: boolean;
  }>(),
  { height: 52, compactHeight: 36, decorative: false, compact: false }
);

const LABEL = 'RIMeF — Réseau International des Médiatrices Francophones';

const logoSource = computed(() => (props.compact ? '/logo-rimef-compact.svg' : '/logo-rimef.svg'));
</script>

<template>
  <picture
    class="app-logo"
    :style="{
      '--logo-height': `${height}px`,
      '--logo-compact-height': `${compactHeight || height}px`,
    }"
  >
    <source
      v-if="compactHeight && !compact"
      media="(max-width: 719px)"
      srcset="/logo-rimef-compact.svg"
    />
    <img :src="logoSource" :alt="decorative ? '' : LABEL" class="app-logo__img" />
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

@media (max-width: 719px) {
  .app-logo__img {
    height: var(--logo-compact-height);
  }
}
</style>
