<script setup lang="ts">
// Logo RIMEF (fiche Logo du design system) : marque aux trois feuilles + mot RIMEF,
// avec la mention « Réseau international des médiatrices francophones » en option.
withDefaults(defineProps<{ withMention?: boolean }>(), { withMention: false });
</script>

<template>
  <span class="app-logo">
    <img src="/favicon.svg" alt="" width="30" height="30" class="app-logo__mark" />
    <span class="app-logo__word">
      <b>RIMEF</b>
      <small v-if="withMention">Réseau international<br />des médiatrices francophones</small>
    </span>
  </span>
</template>

<style lang="scss" scoped>
.app-logo {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--ink);

  &__mark {
    flex: none;
  }

  &__word {
    display: flex;
    flex-direction: column;
    line-height: 1;

    b {
      // noinspection CssNoGenericFontName
      font-family: var(--font-serif);
      font-size: 1.6rem;
      font-weight: 400;
      letter-spacing: 0.01em;
    }

    small {
      margin-top: 3px;
      font-size: 6.5px;
      line-height: 1.3;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--ink-2);
    }
  }
}
</style>
