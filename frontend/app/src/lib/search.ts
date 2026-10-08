// Recherche du bandeau : textes d'invite et rubriques.
// Chaque route peut définir meta.searchPlaceholder et meta.searchScope (router/index.ts).

export const DEFAULT_SEARCH_PLACEHOLDER = 'Rechercher une personne, un événement…';

/** Rubriques transmises à la page de résultats (?rubrique=…) et leur libellé. */
export const SEARCH_SCOPES = {
  reseau: 'Réseau',
  agenda: 'Agenda',
  ressources: 'Ressources',
} as const;

export type SearchScope = keyof typeof SEARCH_SCOPES;

export function isSearchScope(value: unknown): value is SearchScope {
  return typeof value === 'string' && value in SEARCH_SCOPES;
}

/** Au-delà, le texte d'invite est coupé dans le champ de 290 px (sous 1280 px). */
const MAX_PLACEHOLDER_LENGTH_IN_NARROW_FIELD = 30;
const WIDE_FIELD_MIN_SCREEN_WIDTH = 1280;

/** Texte d'invite du champ du bandeau, raccourci s'il ne tient pas dans la largeur. */
export function fitPlaceholder(placeholder: string, screenWidth: number): string {
  const fits =
    screenWidth >= WIDE_FIELD_MIN_SCREEN_WIDTH ||
    placeholder.length <= MAX_PLACEHOLDER_LENGTH_IN_NARROW_FIELD;
  return fits ? placeholder : 'Rechercher…';
}
