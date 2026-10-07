// Client HTTP partagé (axios).
// - baseURL relative `/api` : Traefik route `/api/*` du même domaine vers Laravel,
//   donc pas de CORS ; le cookie de session Sanctum et XSRF-TOKEN suivent seuls.
// - Accept / X-Requested-With : Laravel répond en JSON plutôt qu'en redirection HTML.

import axios, { isAxiosError, type AxiosInstance } from 'axios';

declare module 'axios' {
  interface AxiosRequestConfig {
    /** Ne pas déclencher le retour à la connexion sur 401 (ex. vérification de session). */
    skipUnauthorizedHandler?: boolean;
    /** Interne : la requête a déjà été rejouée après un 419. */
    csrfRetried?: boolean;
  }
}

export const http: AxiosInstance = axios.create({
  baseURL: '/api',
  withCredentials: true,
  timeout: 15000,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

let csrfPromise: Promise<void> | null = null;

/**
 * Obtient le cookie XSRF-TOKEN de Sanctum, à appeler avant la première écriture.
 * Mémoïsé : un seul appel tant que le jeton reste valable.
 */
export function ensureCsrf(): Promise<void> {
  csrfPromise ??= http
    .get('/sanctum/csrf-cookie')
    .then(() => undefined)
    .catch((error: unknown) => {
      csrfPromise = null;
      throw error;
    });
  return csrfPromise;
}

/** Oublie le jeton CSRF (après déconnexion ou jeton expiré). */
export function resetCsrf(): void {
  csrfPromise = null;
}

let unauthorizedHandler: (() => void) | null = null;

/** Action à mener quand la session a expiré (branchée dans main.ts : retour à la connexion). */
export function setUnauthorizedHandler(handler: () => void): void {
  unauthorizedHandler = handler;
}

http.interceptors.response.use(undefined, async (error: unknown) => {
  if (!isAxiosError(error) || !error.config) {
    throw error;
  }

  const { config } = error;
  const status = error.response?.status;

  // 419 : jeton CSRF expiré (session renouvelée côté serveur) → nouveau jeton, un seul essai
  if (status === 419 && !config.csrfRetried) {
    resetCsrf();
    await ensureCsrf();
    return http.request({ ...config, csrfRetried: true });
  }

  if (status === 401 && !config.skipUnauthorizedHandler) {
    unauthorizedHandler?.();
  }

  throw error;
});

/**
 * Message lisible pour l'utilisatrice : 1re erreur de validation, puis message
 * de l'API, puis `fallback`. Les erreurs réseau donnent un message dédié.
 */
export function extractApiError(error: unknown, fallback: string): string {
  if (!isAxiosError(error)) {
    return fallback;
  }

  if (!error.response) {
    return 'Le serveur ne répond pas. Vérifiez votre connexion puis réessayez.';
  }

  if (error.response.status === 429) {
    return 'Trop de tentatives. Patientez une minute avant de réessayer.';
  }

  const data = error.response.data as
    { message?: string; errors?: Record<string, string[]> } | undefined;
  const firstValidationError = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined;

  return firstValidationError ?? data?.message ?? fallback;
}
