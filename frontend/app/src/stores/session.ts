// Session du membre connecté.
// - bootstrap() : une seule vérification au démarrage (cookie de session existant ?)
// - Tout l'espace des membres est réservé aux comptes connectés (voir router/index.ts).

import { isAxiosError } from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

import {
  fetchCurrentUser,
  login as apiLogin,
  logout as apiLogout,
  type AuthenticatedUser,
  type LoginPayload,
} from '@modules/auth/services/auth';

export const useSession = defineStore('session', () => {
  const user = ref<AuthenticatedUser | null>(null);
  const bootstrapped = ref(false);
  let bootstrapPromise: Promise<void> | null = null;

  const authenticated = computed(() => user.value !== null);
  const isAdmin = computed(() => user.value?.roles.includes('admin') ?? false);
  const initials = computed(() =>
    user.value ? `${user.value.first_name.charAt(0)}${user.value.last_name.charAt(0)}` : ''
  );

  function bootstrap(): Promise<void> {
    bootstrapPromise ??= fetchCurrentUser()
      .then((currentUser) => {
        user.value = currentUser;
      })
      .catch(() => {
        user.value = null;
      })
      .finally(() => {
        bootstrapped.value = true;
      });
    return bootstrapPromise;
  }

  async function login(payload: LoginPayload): Promise<void> {
    try {
      await apiLogin(payload);
    } catch (error) {
      // 409 : une session est déjà ouverte (autre onglet) → on la reprend
      if (!(isAxiosError(error) && error.response?.status === 409)) {
        throw error;
      }
    }
    user.value = await fetchCurrentUser();
  }

  async function logout(): Promise<void> {
    try {
      await apiLogout();
    } finally {
      user.value = null;
    }
  }

  /** Session expirée côté serveur : oublie le membre sans appeler l'API. */
  function clear(): void {
    user.value = null;
  }

  return { user, bootstrapped, authenticated, isAdmin, initials, bootstrap, login, logout, clear };
});
