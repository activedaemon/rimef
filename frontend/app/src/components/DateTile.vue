<script setup lang="ts">
// Pastille date « 18 OCT » (fiche DateTile) : jour en Instrument Serif, mois en capitales.
// - size « xl » : agrandie, quand la date est l'information principale (lignes de l'Agenda) ;
// - past : événement terminé, en sauge ;
// - note : sous le mois, ex. « 2 jours ».
withDefaults(
  defineProps<{ day: string; month: string; size?: 'md' | 'xl'; past?: boolean; note?: string }>(),
  { size: 'md', past: false, note: undefined }
);
</script>

<template>
  <span class="date-tile" :class="[`date-tile--${size}`, { 'date-tile--past': past }]">
    <b class="date-tile__day">{{ day }}</b>
    <small class="date-tile__month">{{ month }}</small>
    <i v-if="note" class="date-tile__note">{{ note }}</i>
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

  &__note {
    margin-top: 4px;
    font-size: 10px;
    font-style: normal;
    letter-spacing: 0.04em;
    // Encre de la pastille plutôt que le #b77a66 de la maquette : contraste AA sur 10 px
    color: var(--terracotta-ink);
  }

  &--xl {
    width: 76px;
    height: 84px;

    .date-tile__day {
      font-size: 2.35rem;
    }

    .date-tile__month {
      margin-top: 5px;
      font-size: 11.5px;
    }
  }

  &--past {
    background: var(--sage-light);
    color: #5e6e5d;

    .date-tile__day {
      color: #5e6e5d;
    }

    .date-tile__note {
      color: #5e6e5d;
    }
  }
}

@media (max-width: 719px) {
  .date-tile--xl {
    width: 62px;
    height: 72px;

    .date-tile__day {
      font-size: 1.95rem;
    }
  }
}
</style>
