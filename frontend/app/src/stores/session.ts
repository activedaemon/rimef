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

// Mêmes libellés que App\Enums\Role côté API
const ROLE_LABELS = [
  ['superadmin', 'Superadministrateur'],
  ['admin', 'Administratrice'],
  ['member', 'Membre RIMeF'],
] as const;

export const useSession = defineStore('session', () => {
  const user = ref<AuthenticatedUser | null>(null);
  const bootstrapped = ref(false);
  let bootstrapPromise: Promise<void> | null = null;

  const authenticated = computed(() => user.value !== null);
  const isSuperAdmin = computed(() => user.value?.roles.includes('superadmin') ?? false);
  // Le superadmin a aussi tous les droits d'administration
  const isAdmin = computed(
    () => isSuperAdmin.value || (user.value?.roles.includes('admin') ?? false)
  );
  /** Rôle affiché (menu du compte, profil). */
  const roleLabel = computed(() => {
    if (isSuperAdmin.value) {
      return 'Superadministrateur';
    }
    return isAdmin.value ? 'Administratrice' : 'Membre RIMeF';
  });

  /** Tous les rôles du compte, du plus étendu au plus restreint (carte « Compte » du profil). */
  const roleLabels = computed(() =>
    ROLE_LABELS.filter(([role]) => user.value?.roles.includes(role)).map(([, label]) => label)
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

  /** Recharge le compte après une modification (nom, photo du menu du compte). */
  async function refresh(): Promise<void> {
    user.value = await fetchCurrentUser();
  }

  /** Session expirée côté serveur : oublie le membre sans appeler l'API. */
  function clear(): void {
    user.value = null;
  }

  return {
    user,
    bootstrapped,
    authenticated,
    isSuperAdmin,
    isAdmin,
    roleLabel,
    roleLabels,
    bootstrap,
    login,
    logout,
    refresh,
    clear,
  };
});
