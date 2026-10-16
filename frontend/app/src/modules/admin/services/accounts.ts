// Rubrique Administration : comptes du réseau et invitations (GET/POST/PUT/DELETE /api/admin/…).
// La recherche de la liste vit dans l'URL (?statut=…&role=…&tri=…), comme l'annuaire.
import type { LocationQuery, LocationQueryRaw } from 'vue-router';

import type { FilterOption } from '@/lib/filters';
import { ensureCsrf, http } from '@/lib/http';

export type AccountStatus = 'active' | 'inactive' | 'pending' | 'expired';
export type AccountRole = 'member' | 'admin' | 'superadmin';

export interface Account {
  id: number;
  first_name: string;
  last_name: string;
  name: string;
  email: string;
  photo_url: string | null;
  roles: AccountRole[];
  status: AccountStatus;
  /** Fiche du réseau (/reseau/slug) : médiatrice active seulement. */
  profile_slug: string | null;
  /** Superadmin : ni désactivable, ni supprimable, ni privable de son rôle. */
  is_protected: boolean;
  is_self: boolean;
  /** ISO 8601, UTC. */
  last_login_at: string | null;
  created_at: string | null;
  /** « Membre depuis » : activation, ou création pour les comptes antérieurs ; null pour une invitation. */
  member_since: string | null;
  invited_at: string | null;
  invitation_expires_at: string | null;
  /** Renvoi de l'invitation possible à partir de cette heure (10 minutes après le dernier envoi). */
  invitation_resendable_at: string | null;
  invited_by: string | null;
  /** Complétude de la fiche (pourcentage, éléments manquants) ; null sans rôle Membre ou pour une invitation. */
  profile_completion: { percent: number; missing: string[] } | null;
  /** État de la fiche dans le réseau (fiche d'un compte seulement) ; null sans rôle Membre ou pour une invitation. */
  profile_summary: ProfileSummary | null;
  /** Actions permises au compte qui regarde (UserPolicy). */
  can: {
    toggle_activation: boolean;
    update_roles: boolean;
    delete: boolean;
    manage_invitation: boolean;
    send_message: boolean;
  };
}

export interface ProfileSummary {
  /** Pourcentage d'éléments remplis (photo, fonction, organisation, pays, biographie…). */
  completion: number;
  /** Éléments manquants, en clair : « photo », « biographie »… */
  missing: string[];
  /** Visible dans l'annuaire (compte actif). */
  visible: boolean;
  /** Dernière modification de la fiche (ISO 8601). */
  updated_at: string | null;
  upcoming_events: number;
  resources: number;
}

export interface AccountFacets {
  total: number;
  status: (FilterOption & { value: AccountStatus; count: number })[];
  role: (FilterOption & { value: AccountRole; count: number })[];
}

export interface AccountPage {
  data: Account[];
  meta: {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    per_page: number;
    facets: AccountFacets;
  };
}

export const ROLE_LABELS: Record<AccountRole, string> = {
  superadmin: 'Superadministrateur',
  admin: 'Administratrice',
  member: 'Membre',
};

/** Rôles du plus étendu au plus restreint (ordre des badges). */
export const ROLE_ORDER: AccountRole[] = ['superadmin', 'admin', 'member'];

export const STATUS_LABELS: Record<AccountStatus, string> = {
  active: 'Actif',
  inactive: 'Désactivé',
  pending: 'Invitation en attente',
  expired: 'Invitation expirée',
};

export const FILTER_KEYS = ['status', 'role'] as const;
export type FilterKey = (typeof FILTER_KEYS)[number];

export const FILTER_LABELS: Record<FilterKey, string> = { status: 'Statut', role: 'Rôle' };

/** Nom des filtres dans l'URL, en français comme ailleurs (?statut=pending). */
const FILTER_IN_ROUTE: Record<FilterKey, string> = { status: 'statut', role: 'role' };

export type SortKey = 'name' | 'created' | 'last_login' | 'completion';

export const SORT_OPTIONS: { value: SortKey; label: string }[] = [
  { value: 'name', label: 'Nom' },
  { value: 'created', label: 'Membre depuis' },
  { value: 'last_login', label: 'Dernière connexion' },
  { value: 'completion', label: 'Complétude de la fiche' },
];

const SORT_IN_ROUTE: Record<SortKey, string | null> = {
  name: null,
  created: 'creation',
  last_login: 'connexion',
  completion: 'completude',
};

export const PER_PAGE_OPTIONS = [12, 24, 48] as const;
export type PerPage = (typeof PER_PAGE_OPTIONS)[number];
const DEFAULT_PER_PAGE: PerPage = 12;

export interface AccountQuery {
  q: string;
  selected: Record<FilterKey, string[]>;
  sort: SortKey;
  page: number;
  perPage: PerPage;
}

function toList(value: LocationQuery[string] | undefined): string[] {
  const values = Array.isArray(value) ? value : value == null ? [] : [value];
  return values.filter((item): item is string => typeof item === 'string' && item !== '');
}

export function queryFromRoute(query: LocationQuery): AccountQuery {
  const sort = (Object.keys(SORT_IN_ROUTE) as SortKey[]).find(
    (key) => SORT_IN_ROUTE[key] !== null && SORT_IN_ROUTE[key] === query.tri
  );
  const page = typeof query.page === 'string' ? Number(query.page) : 1;
  const perPage = PER_PAGE_OPTIONS.find((option) => String(option) === query.par_page);
  return {
    q: typeof query.q === 'string' ? query.q : '',
    selected: {
      status: toList(query[FILTER_IN_ROUTE.status]),
      role: toList(query[FILTER_IN_ROUTE.role]),
    },
    sort: sort ?? 'name',
    page: Number.isInteger(page) && page > 1 ? page : 1,
    perPage: perPage ?? DEFAULT_PER_PAGE,
  };
}

export function routeFromQuery(query: AccountQuery): LocationQueryRaw {
  const route: LocationQueryRaw = {};
  if (query.q.trim()) route.q = query.q.trim();
  for (const key of FILTER_KEYS) {
    if (query.selected[key].length) route[FILTER_IN_ROUTE[key]] = [...query.selected[key]];
  }
  const sort = SORT_IN_ROUTE[query.sort];
  if (sort) route.tri = sort;
  if (query.page > 1) route.page = String(query.page);
  if (query.perPage !== DEFAULT_PER_PAGE) route.par_page = String(query.perPage);
  return route;
}

export function isFiltered(query: AccountQuery): boolean {
  return query.q.trim() !== '' || FILTER_KEYS.some((key) => query.selected[key].length > 0);
}

export async function fetchAccounts(
  query: AccountQuery,
  signal?: AbortSignal
): Promise<AccountPage> {
  const params: Record<string, unknown> = {
    page: query.page,
    per_page: query.perPage,
    sort: query.sort,
  };
  if (query.q.trim()) params.q = query.q.trim();
  for (const key of FILTER_KEYS) {
    if (query.selected[key].length) params[key] = query.selected[key];
  }
  const { data } = await http.get<AccountPage>('/admin/accounts', { params, signal });
  return data;
}

export async function fetchAccount(id: number): Promise<Account> {
  const { data } = await http.get<{ data: Account }>(`/admin/accounts/${id}`);
  return data.data;
}

export interface InvitationPayload {
  first_name: string;
  last_name: string;
  email: string;
  /** Superadmin seulement : une administratrice invite toujours un compte membre. */
  roles?: AccountRole[];
}

export async function inviteAccount(payload: InvitationPayload): Promise<Account> {
  await ensureCsrf();
  const { data } = await http.post<{ data: Account }>('/admin/invitations', payload);
  return data.data;
}

export async function resendInvitation(id: number): Promise<Account> {
  await ensureCsrf();
  const { data } = await http.post<{ data: Account }>(`/admin/accounts/${id}/invitation`);
  return data.data;
}

export async function cancelInvitation(id: number): Promise<void> {
  await ensureCsrf();
  await http.delete(`/admin/accounts/${id}/invitation`);
}

export async function setActivation(id: number, active: boolean): Promise<Account> {
  await ensureCsrf();
  const url = `/admin/accounts/${id}/activation`;
  const { data } = active
    ? await http.put<{ data: Account }>(url)
    : await http.delete<{ data: Account }>(url);
  return data.data;
}

export async function updateRoles(id: number, roles: AccountRole[]): Promise<Account> {
  await ensureCsrf();
  const { data } = await http.put<{ data: Account }>(`/admin/accounts/${id}/roles`, { roles });
  return data.data;
}

export async function deleteAccount(id: number): Promise<void> {
  await ensureCsrf();
  await http.delete(`/admin/accounts/${id}`);
}

/** Rôles d'un compte dans l'ordre d'affichage. */
export function sortedRoles(roles: AccountRole[]): AccountRole[] {
  return ROLE_ORDER.filter((role) => roles.includes(role));
}

/** Invitation en attente ou expirée (pas encore de compte activé). */
export function isInvitation(account: Pick<Account, 'status'>): boolean {
  return account.status === 'pending' || account.status === 'expired';
}

const LOCALE = 'fr-FR';

/** « 14 sept. 2026 ». */
export function shortDate(iso: string): string {
  return new Intl.DateTimeFormat(LOCALE, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date(iso));
}

/** « 14 septembre 2026 ». */
export function longDate(iso: string): string {
  return new Intl.DateTimeFormat(LOCALE, { day: 'numeric', month: 'long', year: 'numeric' }).format(
    new Date(iso)
  );
}

/**
 * Dernière connexion : « Aujourd’hui, 07:48 », « Hier, 09:05 », « Il y a 4 jours »,
 * puis « 5 oct. 2026 » ; « Aucune connexion » sans connexion, tiret pour une invitation.
 */
export function lastLoginLabel(
  account: Pick<Account, 'status' | 'last_login_at'>,
  now = new Date()
): string {
  if (isInvitation(account)) return '—';
  if (!account.last_login_at) return 'Aucune connexion';
  const date = new Date(account.last_login_at);
  const startOfDay = (value: Date) =>
    new Date(value.getFullYear(), value.getMonth(), value.getDate()).getTime();
  const days = Math.round((startOfDay(now) - startOfDay(date)) / 86_400_000);
  const time = new Intl.DateTimeFormat(LOCALE, { hour: '2-digit', minute: '2-digit' }).format(date);
  if (days === 0) return `Aujourd’hui, ${time}`;
  if (days === 1) return `Hier, ${time}`;
  if (days < 7) return `Il y a ${days} jours`;
  return shortDate(account.last_login_at);
}

/**
 * Colonne « Activité » de la liste : dernière connexion d'un compte actif, dernière connexion
 * datée d'un compte désactivé, date d'envoi d'une invitation (son auteur est sur la fiche).
 */
export function activityLabel(
  account: Pick<Account, 'status' | 'last_login_at' | 'invited_at'>,
  now = new Date()
): string {
  if (isInvitation(account)) {
    return account.invited_at ? `Envoyée le ${dayMonth(account.invited_at)}` : 'Envoyée';
  }
  if (account.status === 'inactive') {
    return account.last_login_at
      ? `Dernière connexion le ${dayMonth(account.last_login_at)}`
      : 'Aucune connexion';
  }
  return lastLoginLabel(account, now);
}

/** « 16 oct. » (l'année seulement si elle diffère de l'année en cours). */
function dayMonth(iso: string, now = new Date()): string {
  const date = new Date(iso);
  return new Intl.DateTimeFormat(LOCALE, {
    day: 'numeric',
    month: 'short',
    ...(date.getFullYear() === now.getFullYear() ? {} : { year: 'numeric' }),
  }).format(date);
}

/**
 * Échéance d'une invitation en jours calendaires : « Expire dans 7 jours », « Expire demain »,
 * « Expire aujourd’hui », « Expirée hier », « Expirée depuis 3 jours ». urgent : à relancer
 * (expire aujourd'hui ou demain).
 */
export function expiryLabel(
  account: Pick<Account, 'status' | 'invitation_expires_at'>,
  now = new Date()
): { text: string; urgent: boolean } | null {
  if (!isInvitation(account) || !account.invitation_expires_at) return null;
  const startOfDay = (value: Date) =>
    new Date(value.getFullYear(), value.getMonth(), value.getDate()).getTime();
  const days = Math.round(
    (startOfDay(new Date(account.invitation_expires_at)) - startOfDay(now)) / 86_400_000
  );
  if (account.status === 'expired') {
    if (days >= 0) return { text: 'Expirée aujourd’hui', urgent: false };
    if (days === -1) return { text: 'Expirée hier', urgent: false };
    return { text: `Expirée depuis ${-days} jours`, urgent: false };
  }
  if (days <= 0) return { text: 'Expire aujourd’hui', urgent: true };
  if (days === 1) return { text: 'Expire demain', urgent: true };
  return { text: `Expire dans ${days} jours`, urgent: false };
}
