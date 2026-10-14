<script setup lang="ts">
// Pagination de la charte (maquette Réseau) : « Précédent », numéros des pages, « Suivant ».
// Sous 720 px, les numéros laissent la place à « Page n sur N ». Les numéros viennent de
// q-pagination, qui ne propose ni boutons libellés ni ce mode compact.
import { nextTick, ref, watch } from 'vue';

defineProps<{ max: number }>();

const page = defineModel<number>({ required: true });

const numbers = ref<HTMLElement | null>(null);

// Après un clic sur un numéro, le focus reste sur la page en cours (les boutons sont redessinés)
watch(page, async () => {
  if (!numbers.value?.contains(document.activeElement)) return;
  await nextTick();
  numbers.value
    ?.querySelector<HTMLElement>('[aria-current="page"]')
    ?.focus({ preventScroll: true });
});
</script>

<template>
  <div class="app-pagination">
    <q-btn
      unelevated
      no-caps
      :ripple="false"
      icon="chevron-left"
      label="Précédent"
      aria-label="Page précédente"
      class="app-pagination__step"
      :disable="page <= 1"
      @click="page = page - 1"
    />

    <div ref="numbers" class="app-pagination__numbers">
      <q-pagination
        v-model="page"
        :max="max"
        :max-pages="5"
        boundary-numbers
        flat
        active-design="unelevated"
        active-color="primary"
        :ripple="false"
      />
    </div>

    <span class="app-pagination__status">Page {{ page }} sur {{ max }}</span>

    <q-btn
      unelevated
      no-caps
      :ripple="false"
      icon-right="chevron-right"
      label="Suivant"
      aria-label="Page suivante"
      class="app-pagination__step"
      :disable="page >= max"
      @click="page = page + 1"
    />
  </div>
</template>

<style lang="scss" scoped>
.app-pagination {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 4px;
  white-space: nowrap;

  // Survols de la charte à la place du voile gris de Quasar
  :deep(.q-focus-helper) {
    display: none;
  }

  :deep(.q-btn:focus-visible) {
    outline: 2px solid var(--saffron);
    outline-offset: 2px;
  }

  // Précédent / Suivant discrets : texte seul, comme les numéros non sélectionnés
  &__step {
    min-height: 32px;
    padding: 0 8px;
    border-radius: var(--radius-xs);
    background: transparent;
    font-size: var(--fs-sm);
    font-weight: 500;
    color: var(--ink-2);
    transition:
      background 0.15s,
      color 0.15s;

    :deep(.q-icon) {
      font-size: 14px;
    }

    :deep(.q-icon.on-left) {
      margin-right: 6px;
    }

    :deep(.q-icon.on-right) {
      margin-left: 6px;
    }

    // Fond paper : la page est déjà sur fond ivoire
    &:hover:not(.disabled) {
      background: var(--paper);
      color: var(--petrol);
    }

    &.disabled {
      opacity: 1 !important;
      color: #b7b0a3;
    }
  }

  // Numéros : texte ink-2, page en cours pleine en pétrole
  &__numbers :deep(.q-pagination__middle) {
    flex-wrap: nowrap;
    gap: 2px;

    > .q-btn {
      // Quasar écrit padding, min-width et min-height en style en ligne sur chaque numéro
      // (3px 2px, 2em, 0) : seul !important les remplace
      min-width: 32px !important;
      min-height: 32px !important;
      margin: 0;
      padding: 0 4px !important;
      border: 1px solid transparent;
      border-radius: var(--radius-xs);
      font-size: var(--fs-sm);
      font-weight: 500;
      font-variant-numeric: tabular-nums;
      transition:
        background 0.15s,
        border-color 0.15s,
        color 0.15s;
    }

    > .q-btn:not([aria-current='page']) {
      color: var(--ink-2);

      &:hover {
        border-color: var(--border);
        background: var(--paper);
        color: var(--ink);
      }
    }

    // Points de suspension : simple texte atténué
    > .q-btn.disabled {
      min-width: 20px !important;
      padding: 0 !important;
      opacity: 1 !important;
      color: var(--muted);
    }
  }

  &__status {
    display: none;
    font-size: var(--fs-sm);
    color: var(--ink-2);
    font-variant-numeric: tabular-nums;
  }
}

// Téléphone : précédent, « Page n sur N », suivant
@media (max-width: 719px) {
  .app-pagination {
    justify-content: space-between;
    width: 100%;

    &__numbers {
      display: none;
    }

    &__status {
      display: block;
    }

    // Zone tactile de 44 px sur téléphone
    &__step {
      min-height: 44px;
    }
  }
}
</style>
