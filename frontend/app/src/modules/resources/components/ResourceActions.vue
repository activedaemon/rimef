<script setup lang="ts">
// Actions de la fiche ressource (maquette .fr-actions / .fe-dock) : « Consulter la ressource »
// (lien externe, nouvel onglet), « Ajouter aux favoris » (marque-page) et menu « … » (copier le lien ;
// recommander, associer à un événement et signaler affichés désactivés en attendant).
// - dock : barre fixée en bas sur téléphone, menu ouvert vers le haut.
import { copyToClipboard } from 'quasar';
import { computed } from 'vue';

import AppButton from '@/components/AppButton.vue';
import { useNotify } from '@/composables/useNotify';
import type { ResourceDetail } from '../services/resources';
import AppTooltip from '@/components/AppTooltip.vue';

const props = withDefaults(defineProps<{ resource: ResourceDetail; dock?: boolean }>(), {
  dock: false,
});
const emit = defineEmits<{ toggleFavorite: [] }>();

const notify = useNotify();

// Marque-page plein (favori) : tracé SVG de l'icône Tabler « bookmark », rempli par Quasar
// (comme sur les cartes)
const BOOKMARK_FILLED = 'M18 7v14l-6 -4l-6 4v-14a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4z|0 0 24 24';

const menuPosition = computed(() =>
  props.dock
    ? { anchor: 'top right' as const, self: 'bottom right' as const }
    : { anchor: 'bottom right' as const, self: 'top right' as const }
);

async function copyLink(): Promise<void> {
  try {
    await copyToClipboard(window.location.href.split(/[?#]/)[0]!);
    notify.success('Lien de la ressource copié.');
  } catch {
    notify.error('La copie n’a pas fonctionné. Réessayez.');
  }
}
</script>

<template>
  <div class="resource-actions" :class="{ 'resource-actions--dock': dock }">
    <AppButton
      v-if="resource.url"
      :href="resource.url"
      target="_blank"
      rel="noopener noreferrer"
      icon-right="external-link"
      :label="dock ? resource.type.action : 'Consulter la ressource'"
      class="resource-actions__open"
    />

    <AppButton
      variant="quiet"
      :icon="resource.is_favorite ? BOOKMARK_FILLED : 'bookmark'"
      :label="dock ? undefined : resource.is_favorite ? 'Dans mes favoris' : 'Ajouter aux favoris'"
      :aria-label="
        dock ? (resource.is_favorite ? 'Retirer des favoris' : 'Ajouter aux favoris') : undefined
      "
      :aria-pressed="resource.is_favorite"
      class="resource-actions__save"
      :class="{
        'resource-actions__save--on': resource.is_favorite,
        'resource-actions__square': dock,
      }"
      @click="emit('toggleFavorite')"
    />

    <AppButton
      variant="quiet"
      icon="dots"
      aria-label="Plus d’actions"
      aria-haspopup="menu"
      class="resource-actions__square"
    >
      <AppTooltip>Plus d’actions</AppTooltip>
      <q-menu v-bind="menuPosition" :offset="[0, 6]" class="rimef-menu">
        <q-list role="menu" aria-label="Plus d’actions" dense>
          <q-item v-close-popup clickable role="menuitem" @click="copyLink">
            <q-item-section avatar><q-icon name="link" size="16px" /></q-item-section>
            <q-item-section>Copier le lien de la ressource</q-item-section>
          </q-item>
          <q-item disable role="menuitem" aria-disabled="true">
            <q-item-section avatar><q-icon name="share" size="16px" /></q-item-section>
            <q-item-section>Recommander à une médiatrice</q-item-section>
          </q-item>
          <q-item disable role="menuitem" aria-disabled="true">
            <q-item-section avatar><q-icon name="calendar-plus" size="16px" /></q-item-section>
            <q-item-section>Associer à un événement</q-item-section>
          </q-item>
          <q-item disable role="menuitem" aria-disabled="true" class="rimef-menu__separated">
            <q-item-section avatar><q-icon name="flag" size="16px" /></q-item-section>
            <q-item-section>Signaler une information erronée</q-item-section>
          </q-item>
        </q-list>
      </q-menu>
    </AppButton>
  </div>
</template>

<style lang="scss" scoped>
.resource-actions {
  display: flex;
  gap: 10px;
  align-items: center;

  // Bouton carré de la maquette (.btn-square)
  &__square.app-btn {
    flex: none;
    width: 44px;
    min-width: 44px;
    padding: 0;
  }

  // Favori : pétrole clair, sans bordure (maquette .save-btn[aria-pressed='true'])
  &__save--on.app-btn {
    border-color: transparent;
    background: var(--petrol-light);
    color: var(--petrol);
  }

  &--dock .resource-actions__open {
    flex: 1;
  }
}
</style>
