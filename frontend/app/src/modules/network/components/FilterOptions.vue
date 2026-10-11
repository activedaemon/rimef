<script setup lang="ts">
// Contenu d'un filtre : cases à cocher (libellé, aide, effectif) et pied Effacer / Afficher.
// Partagé par le menu déroulant (ordinateur) et la feuille basse (téléphone) de FilterMenu.
// Les cases modifient un brouillon, pris à l'ouverture ; seul « Afficher les résultats »
// l'applique (événement apply). Fermer sans valider abandonne les changements.
import { ref } from 'vue';

import AppButton from '@/components/AppButton.vue';
import type { FilterOption } from '../services/members';

const props = defineProps<{
  options: FilterOption[];
  selected: string[];
  section?: string;
  comfortable?: boolean;
}>();
const emit = defineEmits<{ apply: [values: string[]] }>();

const draft = ref<string[]>([...props.selected]);

function toggle(value: string, checked: boolean): void {
  draft.value = checked ? [...draft.value, value] : draft.value.filter((item) => item !== value);
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
        <q-checkbox
          :dense="!comfortable"
          :model-value="draft.includes(option.value)"
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
  <div class="filter-options__foot">
    <q-btn flat dense no-caps class="link-more" label="Effacer" @click="draft = []" />
    <AppButton
      :size="comfortable ? 'md' : 'sm'"
      class="filter-options__apply"
      label="Afficher les résultats"
      @click="emit('apply', draft)"
    />
  </div>
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

  &__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 6px;
    padding: 8px 4px 2px;
    border-top: 1px solid var(--border);
  }

  // Feuille basse (téléphone) : boutons toujours visibles en bas, sous la liste qui défile
  &--comfortable + &__foot {
    position: sticky;
    bottom: calc(-16px - env(safe-area-inset-bottom, 0px));
    padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px));
    background: var(--card);
  }

  &--comfortable + &__foot &__apply {
    flex: 1;
  }
}
</style>
