// Actualise les non-lus (cloche, « Mes messages ») à l'ouverture, à chaque changement de page
// et quand l'onglet redevient visible. Uniquement pour une session ouverte.
import { onBeforeUnmount, onMounted } from 'vue';
import { useRouter } from 'vue-router';

import { useInbox } from '@/stores/inbox';
import { useSession } from '@/stores/session';

export function useInboxRefresh(): void {
  const inbox = useInbox();
  const session = useSession();
  const router = useRouter();

  const refresh = (force = false) => {
    if (session.authenticated) void inbox.refresh(force);
  };
  const onVisible = () => {
    if (document.visibilityState === 'visible') refresh();
  };

  const removeAfterEach = router.afterEach(() => refresh());
  onMounted(() => {
    refresh(true);
    document.addEventListener('visibilitychange', onVisible);
  });
  onBeforeUnmount(() => {
    removeAfterEach();
    document.removeEventListener('visibilitychange', onVisible);
  });
}
