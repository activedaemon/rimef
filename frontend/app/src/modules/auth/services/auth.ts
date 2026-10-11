// Endpoints d'authentification (Fortify + Sanctum, cookie de session).
// Chemins relatifs au baseURL `/api` de lib/http.ts.

import { isAxiosError } from 'axios';

import { ensureCsrf, http, resetCsrf } from '@/lib/http';

export interface AuthenticatedUser {
  id: number;
  first_name: string;
  last_name: string;
  /** Prénom-nom dans l'URL de sa fiche (/reseau/aminata-diallo). */
  slug: string;
  /** Nom complet (« Prénom Nom »). */
  name: string;
  email: string;
  roles: string[];
  /** Photo de l'annuaire (pastille du menu du compte), null sans photo ou hors annuaire. */
  photo_url: string | null;
}

export interface LoginPayload {
  email: string;
  password: string;
  remember: boolean;
}

export interface ResetPasswordPayload {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export async function login(payload: LoginPayload): Promise<void> {
  await ensureCsrf();
  await http.post('/login', payload, { skipUnauthorizedHandler: true });
}

export async function logout(): Promise<void> {
  await ensureCsrf();
  await http.post('/logout', null, { skipUnauthorizedHandler: true });
  resetCsrf();
}

/** Membre connecté, ou null si aucune session n'est ouverte (401). */
export async function fetchCurrentUser(): Promise<AuthenticatedUser | null> {
  try {
    const { data } = await http.get<{ data: AuthenticatedUser }>('/user', {
      skipUnauthorizedHandler: true,
    });
    return data.data;
  } catch (error) {
    if (isAxiosError(error) && error.response?.status === 401) {
      return null;
    }
    throw error;
  }
}

/** Demande un lien de réinitialisation ; renvoie le message neutre de l'API. */
export async function forgotPassword(email: string): Promise<string> {
  await ensureCsrf();
  const { data } = await http.post<{ message: string }>('/forgot-password', { email });
  return data.message;
}

export async function resetPassword(payload: ResetPasswordPayload): Promise<void> {
  await ensureCsrf();
  await http.post('/reset-password', payload);
}
