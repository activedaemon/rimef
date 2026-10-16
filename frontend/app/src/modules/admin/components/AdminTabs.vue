<script setup lang="ts">
// Rubriques de l'administration, dans le style des onglets de la fiche d'une médiatrice
// (filet bas à la largeur du contenu, texte 13 px, soulignement étroit de 2 px) : « Membres »
// seule active ; les suivantes sont annoncées, grisées et non cliquables (q-tab disable).
defineProps<{ count?: number | null }>();

const UPCOMING = ['Événements', 'Ressources', 'Actualités', 'Journal'];
</script>

<template>
  <nav class="admin-tabs" aria-label="Rubriques de l’administration">
    <div class="container">
      <q-tabs
        no-caps
        align="left"
        active-color="primary"
        indicator-color="primary"
        narrow-indicator
        outside-arrows
        mobile-arrows
        class="admin-tabs__bar"
      >
        <q-route-tab :to="{ name: 'admin' }" class="admin-tabs__tab">
          <span class="q-tab__label">
            Membres<span v-if="count != null" class="admin-tabs__count">{{ count }}</span>
          </span>
        </q-route-tab>
        <q-tab
          v-for="label in UPCOMING"
          :key="label"
          :name="label"
          :label="label"
          disable
          class="admin-tabs__tab admin-tabs__tab--off"
        />
      </q-tabs>
    </div>
  </nav>
</template>

<style lang="scss" scoped>
.admin-tabs {
  &__bar {
    border-bottom: 1px solid var(--border);
    color: var(--ink-2);

    :deep(.q-tab) {
      min-height: 50px;
      padding: 0 12px;
    }

    :deep(.q-tab:first-child) {
      padding-left: 0;
    }

    :deep(.q-tab__label) {
      font-size: var(--fs-sm);
      font-weight: 500;
    }

    :deep(.q-tab--active .q-tab__label) {
      font-weight: 600;
    }

    :deep(.q-tab__indicator) {
      height: 2px;
    }

    :deep(.q-tabs__content) {
      gap: 24px;
    }
  }

  &__count {
    margin-left: 6px;
    font-weight: 400;
    color: var(--muted);
    font-variant-numeric: tabular-nums;
  }

  // Quasar atténue les onglets désactivés à 0,4 : texte gardé lisible (AA), en gris
  &__tab--off {
    opacity: 1 !important;
    color: var(--muted);
  }
}
</style>
