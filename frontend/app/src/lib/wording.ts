// Accords partagés par les écrans (« 1 médiatrice », « 24 médiatrices »).

/** « médiatrice » ou « médiatrices » selon le nombre. */
export function mediatorNoun(count: number): string {
  return count > 1 ? 'médiatrices' : 'médiatrice';
}

/** Nombre suivi de « médiatrice » accordé. */
export function mediatorCount(count: number): string {
  return `${count} ${mediatorNoun(count)}`;
}
