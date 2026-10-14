<script setup lang="ts">
// Couverture générée d'une ressource (maquette .cover) : fond pétrole, tranche plus sombre,
// éditeur en capitales, titre en serif, feuilles en coin, « Rapport · 2026 » en bas. Décorative :
// le titre et l'éditeur sont écrits à côté.
import { LEAF_PATH, leafTransform } from '@/lib/leaf';

defineProps<{ title: string; source: string | null; type: string; year: string }>();
</script>

<template>
  <div class="resource-cover" aria-hidden="true">
    <small v-if="source">{{ source }}</small>
    <b>{{ title }}</b>
    <svg class="resource-cover__leaf" viewBox="0 0 150 130">
      <path :d="LEAF_PATH" :transform="leafTransform(146, 128, -145, 120, 42)" fill="#C86F55" />
      <path :d="LEAF_PATH" :transform="leafTransform(148, 128, -108, 92, 32)" fill="#8FA89C" />
      <path :d="LEAF_PATH" :transform="leafTransform(148, 128, -170, 70, 25)" fill="#D3A64A" />
    </svg>
    <i>{{ type }} · {{ year }}</i>
  </div>
</template>

<style lang="scss" scoped>
.resource-cover {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  aspect-ratio: 3 / 4;
  overflow: hidden;
  padding: 18px 16px;
  border-radius: var(--radius-xs);
  background: var(--petrol-dark);
  box-shadow:
    0 1px 2px rgba(24, 32, 51, 0.06),
    10px 14px 30px -18px rgba(23, 54, 60, 0.55);
  color: var(--on-dark);

  // Tranche du livre
  &::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 6px;
    background: rgba(0, 0, 0, 0.18);
  }

  small {
    font-size: 8.5px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgba(245, 241, 232, 0.72);
  }

  b {
    position: relative;
    z-index: 1;
    margin-top: 12px;
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.28rem;
    font-weight: 400;
    line-height: 1.08;
  }

  &__leaf {
    position: absolute;
    right: -14px;
    bottom: -8px;
    width: 150px;
    height: 130px;
  }

  i {
    position: relative;
    margin-top: auto;
    font-size: 9px;
    font-style: normal;
    letter-spacing: 0.08em;
    color: rgba(245, 241, 232, 0.72);
  }
}
</style>
