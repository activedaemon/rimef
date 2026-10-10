<script setup lang="ts">
// Contenu de la cloche (menu sur ordinateur, plein écran sur téléphone) : en-tête,
// « Tout marquer comme lu », liste des notifications (non lues en gras, filet terracotta).
import MemberAvatar from '@/components/MemberAvatar.vue';
import { shortMoment } from '@/lib/dates';
import type { AppNotification } from '@modules/notifications/services/notifications';

defineProps<{ notifications: AppNotification[]; loading: boolean; closable?: boolean }>();
const emit = defineEmits<{ open: [item: AppNotification]; readAll: []; close: [] }>();
</script>

<template>
  <div class="notification-list">
    <div class="notification-list__head">
      <h2>Notifications</h2>
      <q-btn
        v-if="notifications.some((item) => !item.is_read)"
        flat
        no-caps
        dense
        class="link-more"
        label="Tout marquer comme lu"
        @click="emit('readAll')"
      />
      <q-btn
        v-if="closable"
        flat
        round
        icon="x"
        aria-label="Fermer les notifications"
        @click="emit('close')"
      />
    </div>

    <div v-if="loading && !notifications.length" class="notification-list__state">
      <q-spinner size="24px" color="primary" aria-label="Chargement des notifications" />
    </div>
    <p v-else-if="!notifications.length" class="notification-list__state">
      Aucune notification pour le moment.
    </p>
    <q-list v-else class="notification-list__items">
      <q-item
        v-for="item in notifications"
        :key="item.id"
        clickable
        class="notification-item"
        :class="{ 'notification-item--unread': !item.is_read }"
        @click="emit('open', item)"
      >
        <q-item-section avatar>
          <MemberAvatar :name="item.sender_name ?? item.title" :photo="item.photo_url" :size="40" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="notification-item__title">{{ item.title }}</q-item-label>
          <q-item-label v-if="item.text" caption lines="2">{{ item.text }}</q-item-label>
        </q-item-section>
        <q-item-section side top class="notification-item__time">
          {{ shortMoment(item.created_at) }}
        </q-item-section>
      </q-item>
    </q-list>
  </div>
</template>

<style lang="scss" scoped>
.notification-list {
  background: var(--paper);

  &__head {
    display: flex;
    align-items: center;
    gap: var(--s-4);
    padding: var(--s-4) var(--s-4) var(--s-3);

    h2 {
      margin-right: auto;
      font-size: var(--fs-h3);
    }
  }

  &__state {
    display: grid;
    place-items: center;
    padding: var(--s-8) var(--s-4);
    font-size: var(--fs-sm);
    color: var(--muted);
  }

  &__items {
    padding-bottom: var(--s-2);
  }
}

.notification-item {
  min-height: 64px;
  padding: var(--s-3) var(--s-4);
  border-top: 1px solid var(--border);

  &__title {
    font-size: var(--fs-sm);
    color: var(--ink);
  }

  &__time {
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &--unread {
    box-shadow: inset 3px 0 0 var(--terracotta);

    .notification-item__title {
      font-weight: 600;
    }
  }
}
</style>
