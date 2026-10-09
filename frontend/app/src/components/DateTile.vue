<script setup lang="ts">
// Pastille date « 18 OCT » (fiche DateTile) : jour en Instrument Serif, mois en capitales.
defineProps<{ day: string; month: string }>();
</script>

<template>
  <span class="date-tile">
    <b class="date-tile__day">{{ day }}</b>
    <small class="date-tile__month">{{ month }}</small>
  </span>
</template>

<style lang="scss" scoped>
.date-tile {
  display: flex;
  flex: none;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 54px;
  height: 60px;
  border-radius: var(--radius-xs);
  background: var(--terracotta-light);
  color: var(--terracotta-ink);
  line-height: 1;

  &__day {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.65rem;
    font-weight: 400;
    font-variant-numeric: tabular-nums;
    color: #a8553e; // date-ink
  }

  &__month {
    margin-top: 3px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
  }
}
</style>
