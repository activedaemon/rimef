// Annuaire du réseau : appels à l'API et passage des filtres entre l'URL et l'API.
import type { LocationQuery, LocationQueryRaw } from 'vue-router';

import { http } from '@/lib/http';

export interface Member {
  id: number;
  name: string;
  country: { code: string; name: string } | null;
  region: string | null;
  organization: string | null;
  is_available: boolean;
  expertises: string[];
  languages: { code: string; name: string }[];
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

export const FILTER_KEYS = ['expertise', 'region', 'language', 'organization'] as const;
export type FilterKey = (typeof FILTER_KEYS)[number];

export type MemberFilters = Record<FilterKey, FilterOption[]>;

export const FILTER_LABELS: Record<FilterKey, string> = {
  expertise: 'Expertise',
  region: 'Région',
  language: 'Langue',
  organization: 'Organisation',
};

export type SortKey = 'name' | 'country';

export const SORT_OPTIONS: { value: SortKey; label: string }[] = [
  { value: 'name', label: 'Nom' },
  { value: 'country', label: 'Pays' },
];

/** Recherche en cours : texte, valeurs cochées par filtre, disponibilité et tri. */
export interface DirectoryQuery {
  q: string;
  selected: Record<FilterKey, string[]>;
  available: boolean;
  sort: SortKey;
}

export function emptySelection(): Record<FilterKey, string[]> {
  return { expertise: [], region: [], language: [], organization: [] };
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
  return {
    q: typeof query.q === 'string' ? query.q : '',
    selected,
    available: query.disponible === '1',
    sort: query.tri === 'pays' ? 'country' : 'name',
  };
}

/** Recherche à écrire dans l'URL : seuls les critères renseignés y figurent. */
export function routeFromQuery(query: DirectoryQuery): LocationQueryRaw {
  const route: LocationQueryRaw = {};
  if (query.q.trim()) route.q = query.q.trim();
  for (const key of FILTER_KEYS) {
    if (query.selected[key].length) route[key] = [...query.selected[key]];
  }
  if (query.available) route.disponible = '1';
  if (query.sort === 'country') route.tri = 'pays';
  return route;
}

/** Au moins un critère restreint la liste (texte, filtre ou disponibilité). */
export function isFiltered(query: DirectoryQuery): boolean {
  return (
    query.q.trim() !== '' ||
    query.available ||
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
  if (query.available) params.available = 1;

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
