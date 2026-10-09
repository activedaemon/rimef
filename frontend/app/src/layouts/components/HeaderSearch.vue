<script setup lang="ts">
// Recherche du bandeau (fiche SiteHeader) : champ à partir de 1080 px, bouton loupe en
// dessous (la place manque à côté des onglets) qui ouvre le champ dans une fenêtre.
// Texte d'invite et rubrique propres à chaque page (meta de la route) ; la recherche mène
// à /recherche?q=…&rubrique=…, sauf sur les pages qui filtrent elles-mêmes leur liste
// (meta.searchInPage, ex. l'annuaire) : les termes s'ajoutent alors à l'URL de la page.
import { useQuasar } from 'quasar';
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { DEFAULT_SEARCH_PLACEHOLDER, fitPlaceholder, isSearchScope } from '@/lib/search';

const $q = useQuasar();
const route = useRoute();

const placeholder = computed(() => route.meta.searchPlaceholder ?? DEFAULT_SEARCH_PLACEHOLDER);
const fieldPlaceholder = computed(() => fitPlaceholder(placeholder.value, $q.screen.width));

// Rubrique de la page, ou celle d'une recherche déjà lancée depuis la page de résultats
const scope = computed(() => {
  if (route.meta.searchScope) {
    return route.meta.searchScope;
  }
  return route.name === 'search' && isSearchScope(route.query.rubrique)
    ? route.query.rubrique
    : undefined;
});

const router = useRouter();
const query = ref('');
const dialogOpen = ref(false);
const dialogInput = ref<{ focus: () => void } | null>(null);

// autofocus ne s'applique pas pendant l'animation d'ouverture du q-dialog
function focusDialogInput(): void {
  dialogInput.value?.focus();
}

// Le champ reprend les termes de la page de résultats
watch(
  () => route.query.q,
  (value) => {
    query.value = typeof value === 'string' ? value : '';
  },
  { immediate: true }
);

async function submit(): Promise<void> {
  const terms = query.value.trim();
  if (route.meta.searchInPage) {
    dialogOpen.value = false;
    // Termes vides : la page affiche de nouveau toute sa liste
    const others = { ...route.query };
    delete others.q;
    await router.replace({ query: terms ? { ...others, q: terms } : others });
    return;
  }
  if (terms === '') {
    return;
  }
  dialogOpen.value = false;
  await router.push({
    name: 'search',
    query: scope.value ? { q: terms, rubrique: scope.value } : { q: terms },
  });
}
</script>

<template>
  <form role="search" class="header-search gt-sm" @submit.prevent="submit">
    <q-input
      v-model="query"
      dense
      outlined
      type="search"
      :placeholder="fieldPlaceholder"
      aria-label="Rechercher dans le réseau"
      class="header-search__input"
    >
      <template #prepend><q-icon name="search" size="18px" /></template>
    </q-input>
  </form>

  <q-btn
    flat
    round
    icon="search"
    class="lt-md"
    aria-label="Rechercher"
    aria-haspopup="dialog"
    @click="dialogOpen = true"
  />

  <q-dialog v-model="dialogOpen" position="top" @show="focusDialogInput">
    <q-card class="search-dialog">
      <form role="search" @submit.prevent="submit">
        <q-input
          ref="dialogInput"
          v-model="query"
          dense
          outlined
          type="search"
          :placeholder="placeholder"
          aria-label="Rechercher dans le réseau"
        >
          <template #prepend><q-icon name="search" size="18px" /></template>
        </q-input>
      </form>
    </q-card>
  </q-dialog>
</template>

<style lang="scss" scoped>
.header-search__input {
  width: 290px;

  // Assez large pour le texte d'invite complet (affiché à partir de 1280 px)
  @media (min-width: 1280px) {
    width: 330px;
  }

  :deep(.q-field__control) {
    border-radius: var(--radius-xs);
    background: var(--paper);
  }

  :deep(.q-field__prepend) {
    color: var(--muted);
  }

  :deep(.q-field__native) {
    font-size: var(--fs-sm);
  }
}
</style>

<style lang="scss">
// q-dialog est rendu hors du composant : styles non scopés
.search-dialog {
  width: min(560px, calc(100vw - 2 * var(--gutter)));
  margin-top: var(--s-4);
  padding: var(--s-4);
  background: var(--card);
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-float);

  .q-field__control {
    border-radius: var(--radius-xs);
  }

  .q-field__prepend {
    color: var(--muted);
  }
}
</style>
