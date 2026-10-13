// Bibliothèque : appels à l'API et passage de la recherche entre l'URL et l'API.
import type { LocationQuery, LocationQueryRaw } from 'vue-router';

import type { FilterOption } from '@/lib/filters';
import { ensureCsrf, http } from '@/lib/http';

export interface LibraryResource {
  id: number;
  slug: string;
  title: string;
  /** action : appel à l'action de la carte (« Lire », « Écouter »…). */
  type: { value: string; label: string; action: string };
  /** Organisation éditrice ; null quand l'autrice est une médiatrice du réseau. */
  source: string | null;
  author: { id: number; slug: string; name: string; photo_url: string | null } | null;
  /** « 2026-04-14 ». */
  published_on: string;
  duration_minutes: number | null;
  /** Lien externe (en attendant la fiche ressource). */
  url: string | null;
  themes: { id: number; name: string }[];
  is_saved: boolean;
}

export interface ResourcePage {
  data: LibraryResource[];
  meta: {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
}

/** Avis d'une médiatrice (bandeau « Lues et partagées par les médiatrices »). */
export interface ResourceVoice {
  id: number;
  quote: string;
  resource: { id: number; slug: string; title: string; url: string | null };
  member: {
    id: number;
    slug: string;
    name: string;
    photo_url: string | null;
    country: string | null;
    /** Première expertise de son profil. */
    expertise: string | null;
  };
}

export const FILTER_KEYS = ['type', 'theme', 'region', 'language'] as const;
export type FilterKey = (typeof FILTER_KEYS)[number];

export const FILTER_LABELS: Record<FilterKey, string> = {
  type: 'Type',
  theme: 'Thématique',
  region: 'Région',
  language: 'Langue',
};

/** Valeurs des filtres, dates comprises, et taille de la bibliothèque. */
export interface ResourceFilters extends Record<FilterKey, FilterOption[]> {
  date: FilterOption[];
  total: number;
}

/** Critères oui / non de « Plus de filtres » (paramètre d'API = la clé). */
export const TOGGLE_KEYS = ['linked', 'by_member', 'with_event', 'saved'] as const;
export type ToggleKey = (typeof TOGGLE_KEYS)[number];

export const TOGGLES: Record<ToggleKey, { label: string; hint: string; route: string }> = {
  linked: {
    label: 'Liées à des médiatrices du réseau',
    hint: 'Autrices, contributrices ou recommandations',
    route: 'reseau',
  },
  by_member: {
    label: 'Écrites par une médiatrice RIMeF',
    hint: 'Articles et analyses du réseau',
    route: 'par-une-mediatrice',
  },
  with_event: {
    label: 'Liées à un événement de l’agenda',
    hint: 'Présentées ou discutées lors d’une rencontre',
    route: 'evenement',
  },
  saved: {
    label: 'Mes ressources enregistrées',
    hint: 'Marquées d’un signet',
    route: 'enregistrees',
  },
};

export const SORTS = ['recent', 'oldest', 'title'] as const;
export type SortKey = (typeof SORTS)[number];

export const SORT_LABELS: Record<SortKey, string> = {
  recent: 'Plus récentes',
  oldest: 'Plus anciennes',
  title: 'Titre (A à Z)',
};

/** Tri dans l'URL (?tri=titre), en français ; « Plus récentes » par défaut. */
const SORT_IN_ROUTE: Record<SortKey, string | null> = {
  recent: null,
  oldest: 'anciennes',
  title: 'titre',
};

/** Tailles de page proposées (même liste côté API : ResourceDirectory::PER_PAGE_OPTIONS). */
export const PER_PAGE_OPTIONS = [9, 18, 36] as const;
export type PerPage = (typeof PER_PAGE_OPTIONS)[number];
const DEFAULT_PER_PAGE: PerPage = 9;

/** Recherche en cours : texte, filtres, date, critères oui / non, tri, page et taille. */
export interface ResourceQuery {
  q: string;
  selected: Record<FilterKey, string[]>;
  /** « recent » (moins de 6 mois) ou une année (« 2025 ») ; vide = toutes les dates. */
  date: string;
  toggles: Record<ToggleKey, boolean>;
  sort: SortKey;
  page: number;
  perPage: PerPage;
}

export function emptySelection(): Record<FilterKey, string[]> {
  return { type: [], theme: [], region: [], language: [] };
}

function toList(value: LocationQuery[string] | undefined): string[] {
  const values = Array.isArray(value) ? value : value == null ? [] : [value];
  return values.filter((item): item is string => typeof item === 'string' && item !== '');
}

/** Lit la recherche dans l'URL (?type=podcast&date=2025…), pour qu'un lien la conserve. */
export function queryFromRoute(query: LocationQuery): ResourceQuery {
  const selected = emptySelection();
  for (const key of FILTER_KEYS) {
    selected[key] = toList(query[key]);
  }
  const toggles = Object.fromEntries(
    TOGGLE_KEYS.map((key) => [key, query[TOGGLES[key].route] === '1'])
  ) as Record<ToggleKey, boolean>;
  const sort = SORTS.find((key) => SORT_IN_ROUTE[key] !== null && SORT_IN_ROUTE[key] === query.tri);
  const date =
    typeof query.date === 'string' && /^(recent|\d{4})$/.test(query.date) ? query.date : '';
  const page = typeof query.page === 'string' ? Number(query.page) : 1;
  const perPage = PER_PAGE_OPTIONS.find((option) => String(option) === query.par_page);

  return {
    q: typeof query.q === 'string' ? query.q : '',
    selected,
    date,
    toggles,
    sort: sort ?? 'recent',
    page: Number.isInteger(page) && page > 1 ? page : 1,
    perPage: perPage ?? DEFAULT_PER_PAGE,
  };
}

/** Recherche à écrire dans l'URL : seuls les critères renseignés y figurent. */
export function routeFromQuery(query: ResourceQuery): LocationQueryRaw {
  const route: LocationQueryRaw = {};
  if (query.q.trim()) route.q = query.q.trim();
  for (const key of FILTER_KEYS) {
    if (query.selected[key].length) route[key] = [...query.selected[key]];
  }
  if (query.date) route.date = query.date;
  for (const key of TOGGLE_KEYS) {
    if (query.toggles[key]) route[TOGGLES[key].route] = '1';
  }
  const sort = SORT_IN_ROUTE[query.sort];
  if (sort) route.tri = sort;
  if (query.page > 1) route.page = String(query.page);
  if (query.perPage !== DEFAULT_PER_PAGE) route.par_page = String(query.perPage);
  return route;
}

/** Au moins un critère restreint la bibliothèque (texte, filtre, date, critère oui / non). */
export function isFiltered(query: ResourceQuery): boolean {
  return (
    query.q.trim() !== '' ||
    query.date !== '' ||
    TOGGLE_KEYS.some((key) => query.toggles[key]) ||
    FILTER_KEYS.some((key) => query.selected[key].length > 0)
  );
}

export async function fetchResources(
  query: ResourceQuery,
  signal?: AbortSignal
): Promise<ResourcePage> {
  const params: Record<string, unknown> = {
    page: query.page,
    per_page: query.perPage,
    sort: query.sort,
  };
  if (query.q.trim()) params.q = query.q.trim();
  for (const key of FILTER_KEYS) {
    if (query.selected[key].length) params[key] = query.selected[key];
  }
  if (query.date) params.date = query.date;
  for (const key of TOGGLE_KEYS) {
    if (query.toggles[key]) params[key] = 1;
  }

  const { data } = await http.get<ResourcePage>('/resources', { params, signal });
  return data;
}

export async function fetchResourceFilters(): Promise<ResourceFilters> {
  const { data } = await http.get<{
    data: Record<FilterKey | 'date', (FilterOption & { value: string | number })[]> & {
      total: number;
    };
  }>('/resources/filters');

  // Les thématiques ont un identifiant numérique : en texte, comme dans l'URL
  const asText = (options: (FilterOption & { value: string | number })[]): FilterOption[] =>
    options.map((option) => ({ ...option, value: String(option.value) }));

  return {
    type: asText(data.data.type),
    theme: asText(data.data.theme),
    region: asText(data.data.region),
    language: asText(data.data.language),
    date: asText(data.data.date),
    total: data.data.total,
  };
}

/** Derniers avis des médiatrices. */
export async function fetchVoices(): Promise<ResourceVoice[]> {
  const { data } = await http.get<{ data: ResourceVoice[] }>('/resources/voices');
  return data.data;
}

/** Signet : enregistre la ressource, ou la retire des ressources enregistrées. */
export async function setSaved(resourceId: number, saved: boolean): Promise<void> {
  await ensureCsrf();
  if (saved) {
    await http.put(`/resources/${resourceId}/save`);
  } else {
    await http.delete(`/resources/${resourceId}/save`);
  }
}

/** « 2026-04-14 » → « Avril 2026 ». */
export function publishedLabel(date: string): string {
  const [year, month] = date.split('-').map(Number);
  const label = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(
    new Date(year!, month! - 1, 1)
  );
  return label.charAt(0).toUpperCase() + label.slice(1);
}

const THEME_TONES = ['saffron', 'petrol', 'sage', 'terra'] as const;
export type ThemeTone = (typeof THEME_TONES)[number];

/** Couleur fixe d'une thématique (maquette : tags safran, pétrole, sauge, terracotta). */
export function themeTone(themeId: number): ThemeTone {
  return THEME_TONES[themeId % THEME_TONES.length]!;
}
