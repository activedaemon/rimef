<script setup lang="ts">
// Contenu de la cloche, selon la maquette (.nt-pop) : menu sur ordinateur, plein écran sur
// téléphone. En-tête, « Tout marquer comme lu », les 3 notifications les plus récentes
// (nouvelle : titre en gras et pastille terracotta ; extrait entre guillemets · heure) et lien
// « Voir tous mes messages ». Une notification lue reste affichée, en style normal.
import MemberAvatar from '@/components/MemberAvatar.vue';
import { shortMoment } from '@/lib/dates';
import type { AppNotification } from '@modules/notifications/services/notifications';

defineProps<{ notifications: AppNotification[]; loading: boolean; closable?: boolean }>();
const emit = defineEmits<{ open: [item: AppNotification]; readAll: []; close: [] }>();

const EXCERPT_LENGTH = 50;

/** Extrait court comme la maquette : coupé au dernier mot entier, suivi de « … ». */
function shortExcerpt(text: string): string {
  if (text.length <= EXCERPT_LENGTH) return text;
  const cut = text.slice(0, EXCERPT_LENGTH);
  const lastSpace = cut.lastIndexOf(' ');
  return `${(lastSpace > 20 ? cut.slice(0, lastSpace) : cut).replace(/[\s,;:.]+$/, '')}…`;
}
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
        :ripple="false"
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
        :ripple="false"
        class="notification-item"
        :class="{ 'notification-item--new': !item.is_read }"
        @click="emit('open', item)"
      >
        <MemberAvatar
          :name="item.sender_name ?? item.title"
          :photo="item.photo_url"
          :size="32"
          class="notification-item__avatar"
        />
        <span class="notification-item__text">
          <b>{{ item.title }}</b>
          <small>
            <template v-if="item.text">« {{ shortExcerpt(item.text) }} » · </template>
            <time :datetime="item.created_at">{{ shortMoment(item.created_at) }}</time>
          </small>
        </span>
        <span v-if="!item.is_read" class="sr-only">, nouvelle</span>
      </q-item>
    </q-list>

    <router-link :to="{ name: 'messages' }" class="notification-list__foot" @click="emit('close')">
      Voir tous mes messages
    </router-link>
  </div>
</template>

<style lang="scss" scoped>
// Fenêtre de la maquette : fond presque blanc, marge intérieure 6 px
.notification-list {
  padding: 6px;
  background: #fffefb;

  &__head {
    display: flex;
    align-items: baseline;
    gap: 12px;
    margin-bottom: 4px;
    padding: 10px 10px 12px;
    border-bottom: 1px solid var(--border);

    h2 {
      margin-right: auto;
      font-size: 1.15rem;
      line-height: 1.1;
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
    display: grid;
    gap: 1px;
  }

  &__foot {
    display: block;
    margin-top: 4px;
    padding: 10px;
    font-size: var(--fs-sm);
    color: var(--petrol);
    text-align: center;
    text-decoration: none;
    border-top: 1px solid var(--border);

    &:hover,
    &:focus-visible {
      text-decoration: underline;
      text-underline-offset: 3px;
    }
  }
}

// Ligne de la maquette (.nt) : portrait 32 px, titre et extrait ; nouvelle = gras + pastille
.notification-item {
  position: relative;
  align-items: flex-start;
  gap: 12px;
  min-height: 0;
  padding: 10px 28px 10px 10px;
  font-size: var(--fs-sm);
  line-height: 1.4;
  color: var(--ink-2);
  border-radius: var(--radius-xs);

  &:hover,
  &:focus-visible {
    color: var(--ink);
    background: var(--ivory);
  }

  // Pas de voile gris Quasar au survol : le fond ivoire suffit
  :deep(.q-focus-helper) {
    display: none;
  }

  &__avatar {
    margin-top: 1px;
  }

  &__text {
    min-width: 0;

    b {
      font-weight: 500;
      color: var(--ink);
    }

    small {
      margin-top: 2px;
      font-size: var(--fs-xs);
      color: var(--muted);
      overflow-wrap: anywhere;
      // Extrait long : deux lignes au plus
      display: -webkit-box;
      overflow: hidden;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }
  }

  &--new {
    .notification-item__text b {
      font-weight: 600;
    }

    &::after {
      content: '';
      position: absolute;
      top: 16px;
      right: 12px;
      width: 7px;
      height: 7px;
      background: var(--terracotta);
      border-radius: 50%;
    }
  }
}
</style>
