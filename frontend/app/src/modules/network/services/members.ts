// Annuaire du réseau : appels à l'API et passage des filtres entre l'URL et l'API.
import type { LocationQuery, LocationQueryRaw } from 'vue-router';

import { ensureCsrf, http } from '@/lib/http';

export interface Member {
  id: number;
  /** Prénom-nom dans l'URL de la fiche (/reseau/aminata-diallo). */
  slug: string;
  name: string;
  country: { code: string; name: string } | null;
  region: string | null;
  organization: string | null;
  is_available: boolean;
  is_favorite: boolean;
  /** Prochain événement auquel elle participe (starts_at en ISO 8601, UTC). */
  next_event: { id: number; title: string; starts_at: string } | null;
  photo_url: string | null;
  expertises: string[];
}

/** Fiche d'une médiatrice (écran Profil médiatrice) ; champs vides tant que le profil n'est pas complété. */
export interface MemberProfile {
  id: number;
  slug: string;
  name: string;
  first_name: string;
  photo_url: string | null;
  country: { code: string; name: string } | null;
  city: string | null;
  region: string | null;
  organization: string | null;
  job_title: string | null;
  tagline: string | null;
  bio: string | null;
  years_of_experience: number | null;
  audiences: string | null;
  is_available: boolean;
  is_favorite: boolean;
  expertises: string[];
  zones: { code: string; name: string }[];
}

export interface MemberPage {
  data: Member[];
  meta: { current_page: number; last_page: number; total: number; directory_total: number };
}

export interface FilterOption {
  value: string;
  label: string;
  count?: number;
  hint?: string;
}

export const FILTER_KEYS = ['expertise', 'region', 'organization'] as const;
export type FilterKey = (typeof FILTER_KEYS)[number];

export type MemberFilters = Record<FilterKey, FilterOption[]>;

export const FILTER_LABELS: Record<FilterKey, string> = {
  expertise: 'Expertise',
  region: 'Région',
  organization: 'Organisation',
};

export type SortKey = 'name' | 'country' | 'event';

export const SORT_OPTIONS: { value: SortKey; label: string }[] = [
  { value: 'name', label: 'Nom' },
  { value: 'country', label: 'Pays' },
  { value: 'event', label: 'Prochain événement' },
];

/** Tri dans l'URL (?tri=pays), en français comme les autres critères. */
const SORT_IN_ROUTE: Record<SortKey, string | null> = {
  name: null,
  country: 'pays',
  event: 'evenement',
};

/** Critères oui / non de « Plus de filtres ». */
export const TOGGLE_KEYS = ['available', 'upcoming', 'favorites'] as const;
export type ToggleKey = (typeof TOGGLE_KEYS)[number];

/** Libellé, aide et nom dans l'URL de chaque critère oui / non (paramètre d'API = la clé). */
export const TOGGLES: Record<ToggleKey, { label: string; hint: string; route: string }> = {
  available: {
    label: 'Disponible pour collaboration',
    hint: 'Profils ouverts à de nouveaux projets',
    route: 'disponible',
  },
  upcoming: {
    label: 'Présente à un prochain événement',
    hint: 'Forums, sommets et ateliers à venir',
    route: 'evenement',
  },
  favorites: {
    label: 'Mes favoris',
    hint: 'Profils enregistrés avec le marque-page',
    route: 'favoris',
  },
};

/** Recherche en cours : texte, valeurs cochées par filtre, critères oui / non et tri. */
export interface DirectoryQuery {
  q: string;
  selected: Record<FilterKey, string[]>;
  toggles: Record<ToggleKey, boolean>;
  sort: SortKey;
}

export function emptySelection(): Record<FilterKey, string[]> {
  return { expertise: [], region: [], organization: [] };
}

function toList(value: LocationQuery[string] | undefined): string[] {
  const values = Array.isArray(value) ? value : value == null ? [] : [value];
  return values.filter((item): item is string => typeof item === 'string' && item !== '');
}

/** Lit la recherche dans l'URL (?q=…&region=…), pour qu'un lien la conserve. */
export function queryFromRoute(query: LocationQuery): DirectoryQuery {
  const selected = emptySelection();
  for (const key of FILTER_KEYS) {
    selected[key] = toList(query[key]);
  }
  const toggles = Object.fromEntries(
    TOGGLE_KEYS.map((key) => [key, query[TOGGLES[key].route] === '1'])
  ) as Record<ToggleKey, boolean>;
  const sort = (Object.keys(SORT_IN_ROUTE) as SortKey[]).find(
    (key) => SORT_IN_ROUTE[key] !== null && SORT_IN_ROUTE[key] === query.tri
  );
  return {
    q: typeof query.q === 'string' ? query.q : '',
    selected,
    toggles,
    sort: sort ?? 'name',
  };
}

/** Recherche à écrire dans l'URL : seuls les critères renseignés y figurent. */
export function routeFromQuery(query: DirectoryQuery): LocationQueryRaw {
  const route: LocationQueryRaw = {};
  if (query.q.trim()) route.q = query.q.trim();
  for (const key of FILTER_KEYS) {
    if (query.selected[key].length) route[key] = [...query.selected[key]];
  }
  for (const key of TOGGLE_KEYS) {
    if (query.toggles[key]) route[TOGGLES[key].route] = '1';
  }
  const sort = SORT_IN_ROUTE[query.sort];
  if (sort) route.tri = sort;
  return route;
}

/** Au moins un critère restreint la liste (texte, filtre ou critère oui / non). */
export function isFiltered(query: DirectoryQuery): boolean {
  return (
    query.q.trim() !== '' ||
    TOGGLE_KEYS.some((key) => query.toggles[key]) ||
    FILTER_KEYS.some((key) => query.selected[key].length > 0)
  );
}

export async function fetchMembers(
  query: DirectoryQuery,
  page: number,
  signal?: AbortSignal
): Promise<MemberPage> {
  const params: Record<string, unknown> = { page, sort: query.sort };
  if (query.q.trim()) params.q = query.q.trim();
  for (const key of FILTER_KEYS) {
    if (query.selected[key].length) params[key] = query.selected[key];
  }
  for (const key of TOGGLE_KEYS) {
    if (query.toggles[key]) params[key] = 1;
  }

  const { data } = await http.get<MemberPage>('/members', { params, signal });
  return data;
}

export async function fetchMemberFilters(): Promise<MemberFilters> {
  const { data } = await http.get<{
    data: Record<FilterKey, (FilterOption & { value: string | number })[]>;
  }>('/members/filters');
  // Les identifiants d'expertise arrivent en nombres : tout passe en texte, comme dans l'URL
  const filters = {} as MemberFilters;
  for (const key of FILTER_KEYS) {
    filters[key] = data.data[key].map((option) => ({ ...option, value: String(option.value) }));
  }
  return filters;
}

export async function fetchMemberProfile(
  slug: string,
  signal?: AbortSignal
): Promise<MemberProfile> {
  const { data } = await http.get<{ data: MemberProfile }>(`/members/${encodeURIComponent(slug)}`, {
    signal,
  });
  return data.data;
}

/** Ajoute ou retire une médiatrice des favoris de la personne connectée. */
export async function setFavorite(memberId: number, favorite: boolean): Promise<void> {
  await ensureCsrf();
  if (favorite) {
    await http.put(`/members/${memberId}/favorite`);
  } else {
    await http.delete(`/members/${memberId}/favorite`);
  }
}
