// Non-lus de la personne connectée : pastille de la cloche et compteur de « Mes messages ».
// Actualisés au changement de page et au retour sur l'onglet (pas de temps réel pour l'instant),
// au plus toutes les 20 secondes sauf demande explicite (après une lecture).
import { defineStore } from 'pinia';
import { ref } from 'vue';

import { fetchUnreadCounts } from '@modules/notifications/services/notifications';

const MIN_INTERVAL_MS = 20_000;

export const useInbox = defineStore('inbox', () => {
  const unreadNotifications = ref(0);
  const unreadMessages = ref(0);
  let lastRefresh = 0;
  let pending: Promise<void> | null = null;

  function refresh(force = false): Promise<void> {
    if (pending) return pending;
    if (!force && Date.now() - lastRefresh < MIN_INTERVAL_MS) return Promise.resolve();

    lastRefresh = Date.now();
    pending = fetchUnreadCounts()
      .then((counts) => {
        unreadNotifications.value = counts.notifications;
        unreadMessages.value = counts.messages;
      })
      // Compteur indicatif : un échec ne gêne pas la navigation, il sera réessayé plus tard
      .catch(() => undefined)
      .finally(() => {
        pending = null;
      });
    return pending;
  }

  function reset(): void {
    unreadNotifications.value = 0;
    unreadMessages.value = 0;
    lastRefresh = 0;
  }

  return { unreadNotifications, unreadMessages, refresh, reset };
});
