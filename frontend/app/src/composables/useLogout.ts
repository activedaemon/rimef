import { ref } from 'vue';
import { useRouter } from 'vue-router';

import { useInbox } from '@/stores/inbox';
import { useSession } from '@/stores/session';

/** Déconnexion puis retour à l'écran de connexion, même si l'API ne répond pas. */
export function useLogout() {
  const session = useSession();
  const inbox = useInbox();
  const router = useRouter();
  const loggingOut = ref(false);

  async function logout(): Promise<void> {
    loggingOut.value = true;
    try {
      await session.logout();
    } catch {
      // La session locale est déjà oubliée (voir le store) : on quitte quand même
    } finally {
      loggingOut.value = false;
      inbox.reset();
      await router.replace({ name: 'login' });
    }
  }

  return { logout, loggingOut };
}
