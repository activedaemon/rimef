import { useQuasar, type QNotifyCreateOptions } from 'quasar';

export type NotifyLevel = 'success' | 'error' | 'info';

/** Bouton dans la notification. */
export interface NotifyAction {
  label: string;
  handler: () => void;
}

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

  function show(level: NotifyLevel, message: string, action?: NotifyAction): void {
    $q.notify({
      ...COMMON,
      ...LEVELS[level],
      message,
      // Une action (ex. « Voir la conversation ») laisse le temps de cliquer
      ...(action && {
        timeout: 6000,
        actions: [
          {
            label: action.label,
            color: 'white',
            flat: true,
            noCaps: true,
            handler: action.handler,
          },
        ],
      }),
    });
  }

  return {
    success: (message: string, action?: NotifyAction) => show('success', message, action),
    error: (message: string) => show('error', message),
    info: (message: string) => show('info', message),
  };
}
