// Accords partagés par les écrans (« 1 médiatrice », « 24 médiatrices »).

/** « médiatrice » ou « médiatrices » selon le nombre. */
export function mediatorNoun(count: number): string {
  return count > 1 ? 'médiatrices' : 'médiatrice';
}

/** Nombre suivi de « médiatrice » accordé. */
export function mediatorCount(count: number): string {
  return `${count} ${mediatorNoun(count)}`;
}

/** « événement » ou « événements » selon le nombre. */
export function eventNoun(count: number): string {
  return count > 1 ? 'événements' : 'événement';
}

/** « ressource » ou « ressources » selon le nombre. */
export function resourceNoun(count: number): string {
  return count > 1 ? 'ressources' : 'ressource';
}

/**
 * « de » élidé devant une voyelle : « la fiche d’Olivia », « la fiche de Marcel ».
 * Le h n'est pas élidé (muet ou aspiré selon le prénom : on garde « de »).
 */
export function ofName(name: string): string {
  return /^[aeiouyàâäéèêëîïôöùûüœæ]/i.test(name.trim()) ? `d’${name}` : `de ${name}`;
}
