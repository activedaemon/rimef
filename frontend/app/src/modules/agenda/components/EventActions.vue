<script setup lang="ts">
// Actions de la fiche (maquette .fe-actions / .fe-dock) : « J'y participe » (demande la fenêtre
// « Ma participation »), « Ajouter à mon
// agenda » (Google, Outlook, copier date et lieu) et menu « … » (copier le lien ; recommander
// et signaler affichés désactivés en attendant les notifications).
// - dock : barre fixée en bas sur téléphone, menus en boutons carrés ouverts vers le haut.
// Événement terminé : aucune action (ni participation, ni ajout à l'agenda, ni menu « … »).
import { copyToClipboard } from 'quasar';
import { computed } from 'vue';

import AppButton from '@/components/AppButton.vue';
import { useNotify } from '@/composables/useNotify';
import { formatDateRange } from '@/lib/dates';
import { calendarLinks, type EventDetail } from '../services/events';
import AppTooltip from '@/components/AppTooltip.vue';

const props = withDefaults(defineProps<{ event: EventDetail; dock?: boolean }>(), {
  dock: false,
});
const emit = defineEmits<{ participate: [] }>();

const notify = useNotify();

const isPast = computed(() => props.event.status === 'past');
const dates = computed(() => formatDateRange(props.event.starts_at, props.event.ends_at));
const pageUrl = () => window.location.href.split(/[?#]/)[0]!;
const links = computed(() => calendarLinks(props.event, pageUrl()));

// Menus vers le haut dans la barre du bas, sous le bouton ailleurs
const menuPosition = computed(() =>
  props.dock
    ? { anchor: 'top right' as const, self: 'bottom right' as const }
    : { anchor: 'bottom right' as const, self: 'top right' as const }
);

async function copy(text: string, done: string): Promise<void> {
  try {
    await copyToClipboard(text);
    notify.success(done);
  } catch {
    notify.error('La copie n’a pas fonctionné. Réessayez.');
  }
}

function copyDetails(): void {
  const place = [props.event.venue, props.event.place].filter(Boolean).join(', ');
  void copy(
    [props.event.title, dates.value, place].filter(Boolean).join(', '),
    'Date et lieu copiés.'
  );
}
</script>

<template>
  <div class="event-actions" :class="{ 'event-actions--dock': dock }">
    <AppButton
      v-if="!isPast"
      :icon="event.is_participating ? 'check' : 'calendar-plus'"
      :label="event.is_participating ? 'Je participe' : 'J’y participe'"
      :aria-pressed="event.is_participating"
      aria-haspopup="dialog"
      class="event-actions__join"
      :class="{ 'event-actions__join--on': event.is_participating }"
      @click="emit('participate')"
    />

    <AppButton
      v-if="!isPast"
      variant="quiet"
      icon="calendar"
      :label="dock ? undefined : 'Ajouter à mon agenda'"
      :aria-label="dock ? 'Ajouter à mon agenda' : undefined"
      aria-haspopup="menu"
      :class="{ 'event-actions__square': dock }"
    >
      <q-menu v-bind="menuPosition" :offset="[0, 6]" class="rimef-menu">
        <q-list role="menu" aria-label="Ajouter à mon agenda" dense>
          <q-item-label header class="rimef-menu__label">Ajouter le {{ dates }} à</q-item-label>
          <q-item
            v-close-popup
            clickable
            role="menuitem"
            tag="a"
            :href="links.google"
            target="_blank"
            rel="noopener"
          >
            <q-item-section avatar><q-icon name="brand-google" size="16px" /></q-item-section>
            <q-item-section>Google Agenda</q-item-section>
          </q-item>
          <q-item
            v-close-popup
            clickable
            role="menuitem"
            tag="a"
            :href="links.outlook"
            target="_blank"
            rel="noopener"
          >
            <q-item-section avatar><q-icon name="brand-windows" size="16px" /></q-item-section>
            <q-item-section>Outlook</q-item-section>
          </q-item>
          <q-item
            v-close-popup
            clickable
            role="menuitem"
            class="rimef-menu__separated"
            @click="copyDetails"
          >
            <q-item-section avatar><q-icon name="copy" size="16px" /></q-item-section>
            <q-item-section>Copier date et lieu</q-item-section>
          </q-item>
        </q-list>
      </q-menu>
    </AppButton>

    <AppButton
      v-if="!isPast"
      variant="quiet"
      icon="dots"
      aria-label="Plus d’actions"
      aria-haspopup="menu"
      class="event-actions__square"
    >
      <AppTooltip>Plus d’actions</AppTooltip>
      <q-menu v-bind="menuPosition" :offset="[0, 6]" class="rimef-menu">
        <q-list role="menu" aria-label="Plus d’actions" dense>
          <q-item
            v-close-popup
            clickable
            role="menuitem"
            @click="copy(pageUrl(), 'Lien de l’événement copié.')"
          >
            <q-item-section avatar><q-icon name="link" size="16px" /></q-item-section>
            <q-item-section>Copier le lien de l’événement</q-item-section>
          </q-item>
          <q-item disable role="menuitem" aria-disabled="true">
            <q-item-section avatar><q-icon name="share" size="16px" /></q-item-section>
            <q-item-section>Recommander à une médiatrice</q-item-section>
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
.event-actions {
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

  // Participation enregistrée : sauge, comme le liseré de l'Agenda
  &__join--on.app-btn {
    background: var(--sage-light);
    color: var(--sage-ink);

    &:hover {
      background: #d5ddd3;
    }
  }

  &--dock .event-actions__join {
    flex: 1;
  }
}
</style>
