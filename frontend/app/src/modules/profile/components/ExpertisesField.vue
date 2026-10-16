<script setup lang="ts">
// Domaines d'expertise : choix dans la liste du réseau (pas de création libre), ordre réglable
// (les deux premiers apparaissent sur la carte de l'annuaire), suggestions des plus répandus.
import { computed, ref } from 'vue';

import type { ProfileOptions } from '../services/profile';
import AppTooltip from '@/components/AppTooltip.vue';

const MAX_EXPERTISES = 12;
const MAX_SUGGESTIONS = 6;

const props = defineProps<{ options: ProfileOptions['expertises']; error?: string }>();
const expertises = defineModel<string[]>({ required: true });

const search = ref('');
const available = computed(() =>
  props.options.map((option) => option.name).filter((name) => !expertises.value.includes(name))
);
const filtered = computed(() => {
  const needle = normalize(search.value);
  return available.value.filter((name) => normalize(name).includes(needle));
});
const suggestions = computed(() => available.value.slice(0, MAX_SUGGESTIONS));
const full = computed(() => expertises.value.length >= MAX_EXPERTISES);

function normalize(text: string): string {
  return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}

function onFilter(value: string, update: (callback: () => void) => void): void {
  update(() => {
    search.value = value;
  });
}

function add(name: string | null): void {
  if (name && !full.value && !expertises.value.includes(name)) {
    expertises.value = [...expertises.value, name];
  }
}

function remove(index: number): void {
  expertises.value = expertises.value.filter((_, position) => position !== index);
}

function moveEarlier(index: number): void {
  const next = [...expertises.value];
  [next[index - 1], next[index]] = [next[index]!, next[index - 1]!];
  expertises.value = next;
}
</script>

<template>
  <div class="expertises-field">
    <ul
      v-if="expertises.length"
      class="expertises-field__chips"
      aria-label="Vos domaines d’expertise"
    >
      <li v-for="(expertise, index) in expertises" :key="expertise">
        <q-chip
          removable
          :ripple="false"
          class="expertises-field__chip"
          :class="{ 'expertises-field__chip--card': index < 2 }"
          :remove-aria-label="`Retirer ${expertise}`"
          @remove="remove(index)"
        >
          <q-btn
            v-if="index > 0"
            flat
            dense
            round
            size="xs"
            icon="arrow-left"
            class="expertises-field__move"
            :aria-label="`Placer ${expertise} avant ${expertises[index - 1]}`"
            @click="moveEarlier(index)"
          >
            <AppTooltip>Placer avant {{ expertises[index - 1] }}</AppTooltip>
          </q-btn>
          {{ expertise }}
        </q-chip>
      </li>
    </ul>

    <label class="field-label" for="expertise-add">Ajouter un domaine d’expertise</label>
    <q-select
      :model-value="null"
      for="expertise-add"
      outlined
      dense
      use-input
      hide-selected
      fill-input
      input-debounce="0"
      :options="filtered"
      :disable="full"
      :hint="full ? `${MAX_EXPERTISES} domaines au maximum.` : undefined"
      :error="!!error"
      :error-message="error"
      placeholder="Rechercher dans la liste du réseau"
      class="expertises-field__add"
      @filter="onFilter"
      @update:model-value="add"
    >
      <template #no-option>
        <q-item>
          <q-item-section class="text-grey-7">Aucun domaine ne correspond.</q-item-section>
        </q-item>
      </template>
    </q-select>

    <div v-if="suggestions.length && !full" class="expertises-field__suggest">
      <span>Suggestions du réseau</span>
      <q-chip
        v-for="suggestion in suggestions"
        :key="suggestion"
        clickable
        :ripple="false"
        icon="plus"
        class="expertises-field__suggestion"
        @click="add(suggestion)"
      >
        {{ suggestion }}
      </q-chip>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.expertises-field {
  &__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0 0 18px;
    padding: 0;
    list-style: none;
  }

  &__chip.q-chip {
    margin: 0;
    background: var(--ivory);
    color: var(--ink-2);
  }

  // Les deux premiers : ceux de la carte de l'annuaire
  &__chip--card.q-chip {
    background: var(--petrol-light);
    color: var(--petrol);
  }

  &__move {
    margin: 0 4px 0 -6px;
    color: var(--muted);
  }

  &__add {
    max-width: 460px;
  }

  &__suggest {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-top: 14px;

    > span {
      margin-right: 2px;
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }

  &__suggestion.q-chip {
    margin: 0;
    border: 1px dashed var(--control-border);
    background: transparent;
    color: var(--ink-2);

    &:hover {
      border-color: transparent;
      background: var(--petrol-light);
    }
  }
}
</style>
