<script setup lang="ts">
// Contenu d'un filtre : cases à cocher (libellé, aide, effectif), appliquées dès qu'on les coche.
// Partagé par le menu déroulant (ordinateur) et la feuille basse (téléphone) de FilterMenu.
// single : boutons radio, une seule valeur (filtre Date de l'Agenda).
import type { FilterOption } from '@/lib/filters';

defineProps<{
  options: FilterOption[];
  section?: string;
  comfortable?: boolean;
  single?: boolean;
}>();

const selected = defineModel<string[]>({ required: true });

function toggle(value: string, checked: boolean): void {
  selected.value = checked
    ? [...selected.value, value]
    : selected.value.filter((item) => item !== value);
}
</script>

<template>
  <p v-if="section" class="filter-options__section">{{ section }}</p>
  <q-list
    :dense="!comfortable"
    class="filter-options"
    :class="{ 'filter-options--comfortable': comfortable }"
  >
    <q-item v-for="option in options" :key="option.value" tag="label" class="filter-options__item">
      <q-item-section side>
        <q-radio
          v-if="single"
          :dense="!comfortable"
          :val="option.value"
          :model-value="selected[0] ?? ''"
          @update:model-value="(value: string) => (selected = [value])"
        />
        <q-checkbox
          v-else
          :dense="!comfortable"
          :model-value="selected.includes(option.value)"
          @update:model-value="(checked: boolean) => toggle(option.value, checked)"
        />
      </q-item-section>
      <q-item-section>
        <q-item-label>{{ option.label }}</q-item-label>
        <q-item-label v-if="option.hint" caption>{{ option.hint }}</q-item-label>
      </q-item-section>
      <q-item-section v-if="option.count !== undefined" side class="filter-options__count">
        {{ option.count }}
      </q-item-section>
    </q-item>
  </q-list>
</template>

<style lang="scss" scoped>
.filter-options {
  max-height: 300px;
  overflow: auto;

  &--comfortable {
    max-height: none;
  }

  &__item {
    min-height: 40px;
    padding: 4px 10px;
    border-radius: var(--radius-xs);
    font-size: var(--fs-sm);
    color: var(--ink);

    .filter-options--comfortable & {
      min-height: 48px;
      font-size: var(--fs-body);
    }

    :deep(.q-item__section--side) {
      padding-right: 10px;
    }

    :deep(.q-item__label--caption) {
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }

  &__count {
    font-size: var(--fs-xs);
    color: var(--muted);
    font-variant-numeric: tabular-nums;
  }

  &__section {
    padding: 6px 10px 4px;
    font-size: var(--fs-eyebrow);
    font-weight: 500;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--muted);
  }
}
</style>
