<script setup lang="ts">
// Cloche de l'en-tête : pastille quand il y a du non-lu, liste des dernières notifications
// (menu sous la cloche ; plein écran sur téléphone). Un clic ouvre l'écran concerné et
// marque la notification comme lue.
import { useQuasar } from 'quasar';
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import { useInbox } from '@/stores/inbox';
import {
  fetchNotifications,
  markAllNotificationsRead,
  markNotificationRead,
  type AppNotification,
} from '@modules/notifications/services/notifications';
import NotificationList from './NotificationList.vue';

const $q = useQuasar();
const router = useRouter();
const inbox = useInbox();
const notify = useNotify();

const open = ref(false);
const loading = ref(false);
const notifications = ref<AppNotification[]>([]);

const unread = computed(() => inbox.unreadNotifications);
const buttonLabel = computed(() => {
  if (unread.value === 0) return 'Notifications';
  return unread.value > 1
    ? `Notifications, ${unread.value} nouvelles`
    : 'Notifications, 1 nouvelle';
});
const fullscreen = computed(() => $q.screen.lt.sm);

async function load(): Promise<void> {
  loading.value = true;
  try {
    notifications.value = await fetchNotifications();
  } catch (error) {
    notify.error(extractApiError(error, 'Les notifications n’ont pas pu être chargées.'));
  } finally {
    loading.value = false;
  }
}

// Liste rechargée à chaque ouverture
watch(open, (value) => {
  if (value) void load();
});

async function openNotification(item: AppNotification): Promise<void> {
  open.value = false;
  if (!item.is_read) {
    item.is_read = true;
    void markNotificationRead(item.id)
      .then(() => inbox.refresh(true))
      .catch(() => undefined);
  }
  if (item.path) await router.push(item.path);
}

async function readAll(): Promise<void> {
  try {
    await markAllNotificationsRead();
    notifications.value.forEach((item) => (item.is_read = true));
    await inbox.refresh(true);
  } catch (error) {
    notify.error(
      extractApiError(error, 'Les notifications n’ont pas pu être marquées comme lues.')
    );
  }
}
</script>

<template>
  <q-btn
    flat
    round
    icon="bell"
    class="notification-bell"
    :aria-label="buttonLabel"
    aria-haspopup="dialog"
    @click="fullscreen && (open = true)"
  >
    <span v-if="unread > 0" class="notification-bell__dot" aria-hidden="true" />

    <!-- Sur ordinateur, q-menu s'ouvre seul au clic sur la cloche -->
    <q-menu
      v-if="!fullscreen"
      v-model="open"
      anchor="bottom right"
      self="top right"
      :offset="[0, 8]"
      class="notification-menu"
    >
      <NotificationList
        :notifications="notifications"
        :loading="loading"
        @open="openNotification"
        @read-all="readAll"
      />
    </q-menu>
  </q-btn>

  <q-dialog
    v-if="fullscreen"
    v-model="open"
    maximized
    transition-show="slide-up"
    transition-hide="slide-down"
  >
    <NotificationList
      :notifications="notifications"
      :loading="loading"
      closable
      @open="openNotification"
      @read-all="readAll"
      @close="open = false"
    />
  </q-dialog>
</template>

<style lang="scss" scoped>
.notification-bell {
  position: relative;
  color: var(--ink-2);

  &__dot {
    position: absolute;
    top: 9px;
    right: 9px;
    width: 10px;
    height: 10px;
    border: 2px solid var(--paper);
    border-radius: 50%;
    background: var(--terracotta);
  }
}
</style>

<style lang="scss">
// Menu de la cloche (téléporté hors du composant)
.notification-menu {
  width: 380px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-float);
  max-width: calc(100vw - 32px);
  max-height: min(520px, calc(100vh - 96px));
}
</style>
