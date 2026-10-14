<script setup lang="ts">
// Zones d'intervention : un pays par ligne, dans l'ordre de saisie, ajout par recherche.
import { computed, ref } from 'vue';

import type { ProfileOptions } from '../services/profile';

const MAX_ZONES = 12;

const props = defineProps<{ countries: ProfileOptions['countries']; error?: string }>();
const zones = defineModel<string[]>({ required: true });

const search = ref('');
const names = computed(
  () => new Map(props.countries.map((country) => [country.code, country.name]))
);
const filtered = computed(() => {
  const needle = normalize(search.value);
  return props.countries
    .filter((country) => !zones.value.includes(country.code))
    .filter((country) => normalize(country.name).includes(needle))
    .map((country) => ({ value: country.code, label: country.name }));
});
const full = computed(() => zones.value.length >= MAX_ZONES);

function normalize(text: string): string {
  return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}

function onFilter(value: string, update: (callback: () => void) => void): void {
  update(() => {
    search.value = value;
  });
}

function add(code: string | null): void {
  if (code && !full.value && !zones.value.includes(code)) {
    zones.value = [...zones.value, code];
  }
}

function remove(code: string): void {
  zones.value = zones.value.filter((zone) => zone !== code);
}
</script>

<template>
  <div class="zones-field">
    <q-list
      v-if="zones.length"
      bordered
      separator
      class="zones-field__list"
      aria-label="Vos zones d’intervention"
    >
      <q-item v-for="zone in zones" :key="zone" class="zones-field__item">
        <q-item-section>{{ names.get(zone) ?? zone }}</q-item-section>
        <q-item-section side>
          <q-btn
            flat
            round
            icon="x"
            :aria-label="`Retirer ${names.get(zone) ?? zone}`"
            @click="remove(zone)"
          />
        </q-item-section>
      </q-item>
    </q-list>

    <label class="field-label" for="zone-add">Pays à ajouter</label>
    <q-select
      :model-value="null"
      for="zone-add"
      outlined
      dense
      use-input
      hide-selected
      fill-input
      input-debounce="0"
      emit-value
      map-options
      :options="filtered"
      :disable="full"
      :hint="full ? `${MAX_ZONES} zones au maximum.` : undefined"
      :error="!!error"
      :error-message="error"
      placeholder="Rechercher un pays"
      class="zones-field__add"
      @filter="onFilter"
      @update:model-value="add"
    >
      <template #no-option>
        <q-item>
          <q-item-section class="text-grey-7">Aucun pays ne correspond.</q-item-section>
        </q-item>
      </template>
    </q-select>
  </div>
</template>

<style lang="scss" scoped>
.zones-field {
  &__list {
    margin-bottom: 18px;
    border-color: var(--border);
    border-radius: var(--radius-sm);
    background: var(--paper);
  }

  &__item {
    min-height: 52px;
  }

  &__add {
    max-width: 460px;
  }
}
</style>
