// Dates affichées dans le fuseau de l'appareil (l'API les donne en UTC, ISO 8601).

const LOCALE = 'fr-FR';

/** « 18 octobre 2026 », « 12–13 novembre 2026 », « 30 novembre – 2 décembre 2026 ». */
export function formatDateRange(startIso: string, endIso?: string | null): string {
  const start = new Date(startIso);
  const full = new Intl.DateTimeFormat(LOCALE, { day: 'numeric', month: 'long', year: 'numeric' });
  if (!endIso) return full.format(start);

  const end = new Date(endIso);
  if (start.toDateString() === end.toDateString()) return full.format(start);
  if (start.getFullYear() !== end.getFullYear()) {
    return `${full.format(start)} – ${full.format(end)}`;
  }
  if (start.getMonth() === end.getMonth()) {
    return `${start.getDate()}–${full.format(end)}`;
  }
  const dayMonth = new Intl.DateTimeFormat(LOCALE, { day: 'numeric', month: 'long' });
  return `${dayMonth.format(start)} – ${full.format(end)}`;
}

/** Pastille date (fiche DateTile) : jour et mois abrégé en capitales, « 18 » et « OCT ». */
export function dayAndMonth(iso: string): { day: string; month: string } {
  const date = new Date(iso);
  const month = new Intl.DateTimeFormat(LOCALE, { month: 'short' })
    .format(date)
    .replace('.', '')
    .slice(0, 4)
    .toUpperCase();
  return { day: String(date.getDate()), month };
}

/** « Aujourd’hui », « Hier », « Il y a 3 jours » (en jours calendaires). */
export function daysAgo(iso: string, now: Date = new Date()): string {
  const startOfDay = (date: Date) =>
    new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
  const days = Math.round((startOfDay(now) - startOfDay(new Date(iso))) / 86_400_000);
  const text = new Intl.RelativeTimeFormat(LOCALE, { numeric: 'auto' }).format(-days, 'day');
  return text.charAt(0).toUpperCase() + text.slice(1);
}
