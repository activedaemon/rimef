import { useQuasar, type QNotifyCreateOptions } from 'quasar';

export type NotifyLevel = 'success' | 'error' | 'info';

// Couleurs d'état de la charte (quasar.variables.scss), contrastées AA avec un texte blanc
const COMMON: QNotifyCreateOptions = { textColor: 'white', position: 'bottom-right' };

const LEVELS: Record<NotifyLevel, QNotifyCreateOptions> = {
  success: { color: 'positive', icon: 'circle-check', timeout: 2500 },
  info: { color: 'info', icon: 'info-circle', timeout: 3000 },
  // Une erreur reste plus longtemps et peut être fermée après lecture
  error: {
    color: 'negative',
    icon: 'alert-circle',
    timeout: 6000,
    actions: [{ label: 'Fermer', color: 'white', flat: true, noCaps: true }],
  },
};

/** Notifications de l'application, en trois niveaux : succès, échec, infos. */
export function useNotify() {
  const $q = useQuasar();

  function show(level: NotifyLevel, message: string): void {
    $q.notify({ ...COMMON, ...LEVELS[level], message });
  }

  return {
    success: (message: string) => show('success', message),
    error: (message: string) => show('error', message),
    info: (message: string) => show('info', message),
  };
}
