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

function isSameDay(a: Date, b: Date): boolean {
  return a.toDateString() === b.toDateString();
}

function daysBefore(now: Date, days: number): Date {
  return new Date(now.getFullYear(), now.getMonth(), now.getDate() - days);
}

function capitalize(text: string): string {
  return text.charAt(0).toUpperCase() + text.slice(1);
}

/**
 * Liste des messages et cloche : « 14:32 » aujourd’hui, « Hier », « Mar. » dans la semaine,
 * puis « 12 oct. » (« 12 oct. 2025 » une autre année).
 */
export function shortMoment(iso: string, now: Date = new Date()): string {
  const date = new Date(iso);
  if (isSameDay(date, now)) return timeOfDay(iso);
  if (isSameDay(date, daysBefore(now, 1))) return 'Hier';
  if (date > daysBefore(now, 6)) {
    const weekday = new Intl.DateTimeFormat(LOCALE, { weekday: 'long' }).format(date);
    return `${capitalize(weekday.slice(0, 3))}.`;
  }
  return new Intl.DateTimeFormat(LOCALE, {
    day: 'numeric',
    month: 'short',
    ...(date.getFullYear() === now.getFullYear() ? {} : { year: 'numeric' }),
  }).format(date);
}

/** Heure d'un message : « 14:32 ». */
export function timeOfDay(iso: string): string {
  return new Intl.DateTimeFormat(LOCALE, { hour: '2-digit', minute: '2-digit' }).format(
    new Date(iso)
  );
}

/**
 * Séparateur de jour d'une conversation : « Aujourd’hui », « Hier », « Lundi 5 octobre »,
 * « Jeudi 1er janvier 2025 » une autre année.
 */
export function dayLabel(iso: string, now: Date = new Date()): string {
  const date = new Date(iso);
  if (isSameDay(date, now)) return 'Aujourd’hui';
  if (isSameDay(date, daysBefore(now, 1))) return 'Hier';
  const weekday = new Intl.DateTimeFormat(LOCALE, { weekday: 'long' }).format(date);
  const month = new Intl.DateTimeFormat(LOCALE, { month: 'long' }).format(date);
  const day = date.getDate() === 1 ? '1er' : String(date.getDate());
  const year = date.getFullYear() === now.getFullYear() ? '' : ` ${date.getFullYear()}`;
  return capitalize(`${weekday} ${day} ${month}${year}`);
}

/** Heure complète d'un message : « 10 octobre 2026 à 14:32 ». */
export function fullMoment(iso: string): string {
  return new Intl.DateTimeFormat(LOCALE, { dateStyle: 'long', timeStyle: 'short' }).format(
    new Date(iso)
  );
}
