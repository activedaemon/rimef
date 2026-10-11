// Filtres en pilule (FilterMenu) partagés par les listes : valeurs proposées par l'API.

/** Valeur d'un filtre : libellé, aide facultative et nombre d'éléments concernés. */
export interface FilterOption {
  value: string;
  label: string;
  count?: number;
  hint?: string;
}

/** Libellé d'une valeur cochée, pour les filtres actifs retirables (la valeur à défaut). */
export function optionLabel(options: FilterOption[], value: string): string {
  return options.find((option) => option.value === value)?.label ?? value;
}
