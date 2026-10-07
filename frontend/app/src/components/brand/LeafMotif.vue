<script setup lang="ts">
// Motif feuille (fiche LeafMotif) : deux feuilles en éventail, terracotta et sauge,
// repris des intros de page de la maquette. Seul ornement de la plateforme, décoratif.
const LEAF_PATH = 'M1 20 C 22 -1, 66 -3, 99 20 C 66 43, 22 41, 1 20 Z';
</script>

<template>
  <svg class="leaf-motif" viewBox="0 0 120 96" aria-hidden="true" focusable="false">
    <path
      :d="LEAF_PATH"
      transform="translate(112 90) rotate(-150) translate(0 -16) scale(0.92 0.8)"
      fill="#C86F55"
      opacity="0.85"
    />
    <path
      :d="LEAF_PATH"
      transform="translate(114 92) rotate(-112) translate(0 -12.5) scale(0.7 0.625)"
      fill="#8FA89C"
      opacity="0.85"
    />
  </svg>
</template>

<style lang="scss" scoped>
.leaf-motif {
  display: block;
  pointer-events: none;
}
</style>
