/**
 * Destination après connexion, prise dans `?redirect=` : seulement un chemin interne
 * (« /reseau »), jamais une URL externe (« //site.com », « https://… »), pour
 * qu'un lien piégé ne renvoie pas vers un autre site.
 */
export function safeRedirect(value: unknown, fallback = '/'): string {
  if (typeof value !== 'string' || !value.startsWith('/')) {
    return fallback;
  }
  if (value.startsWith('//') || value.startsWith('/\\')) {
    return fallback;
  }
  return value;
}
