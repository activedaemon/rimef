// Agenda : appels à l'API et passage de la recherche entre l'URL et l'API.
import type { LocationQuery, LocationQueryRaw } from 'vue-router';

import type { FilterOption } from '@/lib/filters';
import { ensureCsrf, http } from '@/lib/http';

export interface Attendee {
  id: number;
  slug: string;
  name: string;
  photo_url: string | null;
}

export interface AgendaEvent {
  id: number;
  /** Adresse de la fiche : /agenda/paris-peace-forum-2026. */
  slug: string;
  title: string;
  type: { value: string; label: string } | null;
  /** ISO 8601, UTC. */
  starts_at: string;
  ends_at: string | null;
  /** « Ville, Pays » ou « En ligne ». */
  place: string | null;
  is_online: boolean;
  is_past: boolean;
  themes: string[];
  /** Médiatrices de l'annuaire inscrites ; attendees n'en donne que les premières. */
  attendee_count: number;
  attendees: Attendee[];
  is_participating: boolean;
}

export interface EventPage {
  data: AgendaEvent[];
  meta: {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
}

/** Onglets : à venir, passés, ceux de la personne connectée. */
export const PERIODS = ['upcoming', 'past', 'mine'] as const;
export type Period = (typeof PERIODS)[number];

export const PERIOD_LABELS: Record<Period, string> = {
  upcoming: 'À venir',
  past: 'Passés',
  mine: 'Mes événements',
};

/** Onglet dans l'URL (?onglet=passes), en français ; « À venir » par défaut. */
const PERIOD_IN_ROUTE: Record<Period, string | null> = {
  upcoming: null,
  past: 'passes',
  mine: 'mes-evenements',
};

export const FILTER_KEYS = ['type', 'region', 'theme'] as const;
export type FilterKey = (typeof FILTER_KEYS)[number];

export const FILTER_LABELS: Record<FilterKey, string> = {
  type: 'Type',
  region: 'Région',
  theme: 'Thématique',
};

/** Valeurs des filtres de l'onglet, mois compris, et nombre d'événements de chaque onglet. */
export interface EventFilters extends Record<FilterKey, FilterOption[]> {
  month: FilterOption[];
  periods: Record<Period, number>;
  /** Critères personnels utilisables : ils demandent des zones et des expertises au profil. */
  toggles: Record<PersonalToggleKey, boolean>;
}

/** Critères oui / non de « Plus de filtres » (paramètre d'API = la clé). */
export const TOGGLE_KEYS = ['favorites', 'my_zones', 'my_themes', 'online', 'crowd'] as const;
export type ToggleKey = (typeof TOGGLE_KEYS)[number];

/** Critères tirés du profil, proposés seulement s'il les renseigne. */
export type PersonalToggleKey = 'my_zones' | 'my_themes';

export const TOGGLES: Record<ToggleKey, { label: string; hint: string; route: string }> = {
  favorites: {
    label: 'Avec mes favorites',
    hint: 'Une médiatrice de vos favoris y participe',
    route: 'mes-favorites',
  },
  my_zones: {
    label: 'Dans mes zones d’intervention',
    hint: 'Dans un pays où vous intervenez',
    route: 'mes-zones',
  },
  my_themes: {
    label: 'Sur mes thématiques',
    hint: 'Liées à vos expertises',
    route: 'mes-thematiques',
  },
  online: { label: 'Format en ligne', hint: 'Formations et webinaires', route: 'en-ligne' },
  crowd: {
    label: '5 médiatrices du réseau ou plus',
    hint: 'Pour se retrouver sur place',
    route: 'affluence',
  },
};

export type SortKey = 'date' | 'attendees';

/** Tailles de page proposées (même liste côté API : EventDirectory::PER_PAGE_OPTIONS). */
export const PER_PAGE_OPTIONS = [10, 20, 50] as const;
export type PerPage = (typeof PER_PAGE_OPTIONS)[number];
const DEFAULT_PER_PAGE: PerPage = 10;

/** Recherche en cours : onglet, texte, filtres, mois ou jour, critères oui / non, tri, page et taille. */
export interface AgendaQuery {
  period: Period;
  q: string;
  selected: Record<FilterKey, string[]>;
  /** « 2026-11 », vide = toutes les dates. */
  month: string;
  /** Jour choisi dans le mini-calendrier, « 2026-11-12 » ; vide = aucun. */
  day: string;
  toggles: Record<ToggleKey, boolean>;
  sort: SortKey;
  page: number;
  perPage: PerPage;
}

export function emptySelection(): Record<FilterKey, string[]> {
  return { type: [], region: [], theme: [] };
}

function toList(value: LocationQuery[string] | undefined): string[] {
  const values = Array.isArray(value) ? value : value == null ? [] : [value];
  return values.filter((item): item is string => typeof item === 'string' && item !== '');
}

/** Lit la recherche dans l'URL (?onglet=passes&type=workshop…), pour qu'un lien la conserve. */
export function queryFromRoute(query: LocationQuery): AgendaQuery {
  const selected = emptySelection();
  for (const key of FILTER_KEYS) {
    selected[key] = toList(query[key]);
  }
  const toggles = Object.fromEntries(
    TOGGLE_KEYS.map((key) => [key, query[TOGGLES[key].route] === '1'])
  ) as Record<ToggleKey, boolean>;
  const period = PERIODS.find(
    (key) => PERIOD_IN_ROUTE[key] !== null && PERIOD_IN_ROUTE[key] === query.onglet
  );
  const month =
    typeof query.mois === 'string' && /^\d{4}-\d{2}$/.test(query.mois) ? query.mois : '';
  const day =
    typeof query.jour === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(query.jour) ? query.jour : '';
  const page = typeof query.page === 'string' ? Number(query.page) : 1;
  const perPage = PER_PAGE_OPTIONS.find((option) => String(option) === query.par_page);

  return {
    period: period ?? 'upcoming',
    q: typeof query.q === 'string' ? query.q : '',
    selected,
    month,
    day,
    toggles,
    sort: query.tri === 'mediatrices' ? 'attendees' : 'date',
    page: Number.isInteger(page) && page > 1 ? page : 1,
    perPage: perPage ?? DEFAULT_PER_PAGE,
  };
}

/** Recherche à écrire dans l'URL : seuls les critères renseignés y figurent. */
export function routeFromQuery(query: AgendaQuery): LocationQueryRaw {
  const route: LocationQueryRaw = {};
  const period = PERIOD_IN_ROUTE[query.period];
  if (period) route.onglet = period;
  if (query.q.trim()) route.q = query.q.trim();
  for (const key of FILTER_KEYS) {
    if (query.selected[key].length) route[key] = [...query.selected[key]];
  }
  if (query.month) route.mois = query.month;
  if (query.day) route.jour = query.day;
  for (const key of TOGGLE_KEYS) {
    if (query.toggles[key]) route[TOGGLES[key].route] = '1';
  }
  if (query.sort === 'attendees') route.tri = 'mediatrices';
  if (query.page > 1) route.page = String(query.page);
  if (query.perPage !== DEFAULT_PER_PAGE) route.par_page = String(query.perPage);
  return route;
}

/** Au moins un critère restreint la liste de l'onglet (texte, filtre, mois, critère oui / non). */
export function isFiltered(query: AgendaQuery): boolean {
  return (
    query.q.trim() !== '' ||
    query.month !== '' ||
    query.day !== '' ||
    TOGGLE_KEYS.some((key) => query.toggles[key]) ||
    FILTER_KEYS.some((key) => query.selected[key].length > 0)
  );
}

/** Fuseau de l'appareil : l'API y lit les mois et les jours. */
function timezone(): string {
  return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

export async function fetchEvents(query: AgendaQuery, signal?: AbortSignal): Promise<EventPage> {
  const params: Record<string, unknown> = {
    period: query.period,
    page: query.page,
    per_page: query.perPage,
    sort: query.sort,
    timezone: timezone(),
  };
  if (query.q.trim()) params.q = query.q.trim();
  for (const key of FILTER_KEYS) {
    if (query.selected[key].length) params[key] = query.selected[key];
  }
  if (query.month) params.month = query.month;
  if (query.day) params.day = query.day;
  for (const key of TOGGLE_KEYS) {
    if (query.toggles[key]) params[key] = 1;
  }

  const { data } = await http.get<EventPage>('/events', { params, signal });
  return data;
}

export async function fetchEventFilters(period: Period): Promise<EventFilters> {
  const { data } = await http.get<{
    data: Record<FilterKey | 'month', (FilterOption & { value: string | number })[]> & {
      periods: Record<Period, number>;
      toggles: Record<PersonalToggleKey, boolean>;
    };
  }>('/events/filters', { params: { period, timezone: timezone() } });

  // Les thématiques ont un identifiant numérique : en texte, comme dans l'URL
  const asText = (options: (FilterOption & { value: string | number })[]): FilterOption[] =>
    options.map((option) => ({ ...option, value: String(option.value) }));

  return {
    type: asText(data.data.type),
    region: asText(data.data.region),
    theme: asText(data.data.theme),
    month: asText(data.data.month),
    periods: data.data.periods,
    toggles: data.data.toggles,
  };
}

/** Jour du mini-calendrier et titres de ses événements. */
export interface CalendarDay {
  /** « 2026-11-12 », dans le fuseau de l'appareil. */
  date: string;
  events: { id: number; title: string }[];
}

/** Jours d'un mois (« 2026-11 ») qui ont au moins un événement, tous onglets confondus. */
export async function fetchCalendar(month: string): Promise<CalendarDay[]> {
  const { data } = await http.get<{ data: CalendarDay[] }>('/events/calendar', {
    params: { month, timezone: timezone() },
  });
  return data.data;
}

/** Jours de présence (null = tous les jours) et note facultative. */
export interface Attendance {
  days: string[] | null;
  note: string | null;
}

/**
 * Fenêtre « Ma participation » : inscription ou mise à jour, avec les jours de présence (null =
 * tous), la note et la proposition de se retrouver sur place.
 */
export async function saveParticipation(
  eventId: number,
  values: Attendance & { wants_to_meet: boolean }
): Promise<void> {
  await ensureCsrf();
  await http.put(`/events/${eventId}/participation`, { ...values, timezone: timezone() });
}

/** « J'y participe » : inscription ou désinscription de la personne connectée. */
export async function setParticipation(eventId: number, participating: boolean): Promise<void> {
  await ensureCsrf();
  if (participating) {
    await http.put(`/events/${eventId}/participation`);
  } else {
    await http.delete(`/events/${eventId}/participation`);
  }
}

/**
 * « Proposer de se retrouver sur place » : proposition de la personne connectée, qui l'inscrit
 * aussi à l'événement, ou retrait de la proposition.
 */
export async function setMeeting(eventId: number, available: boolean): Promise<void> {
  await ensureCsrf();
  if (available) {
    await http.put(`/events/${eventId}/meeting`);
  } else {
    await http.delete(`/events/${eventId}/meeting`);
  }
}

/** Mois d'un événement dans le fuseau de l'appareil (« 2026-11 »), pour les intertitres. */
export function monthKey(iso: string): string {
  const date = new Date(iso);
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

/** « 2026-11 » → « Novembre 2026 ». */
export function monthLabel(key: string): string {
  const [year, month] = key.split('-').map(Number);
  const label = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(
    new Date(year!, month! - 1, 1)
  );
  return label.charAt(0).toUpperCase() + label.slice(1);
}

/** « 2026-11-12 » → « Le 12 novembre 2026 ». */
export function dayLabel(day: string): string {
  const [year, month, date] = day.split('-').map(Number);
  const label = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date(year!, month! - 1, date!));
  return `Le ${label}`;
}

/** Jour de l'appareil, « 2026-10-12 ». */
export function todayKey(now: Date = new Date()): string {
  const month = String(now.getMonth() + 1).padStart(2, '0');
  return `${now.getFullYear()}-${month}-${String(now.getDate()).padStart(2, '0')}`;
}

/** Événements regroupés par mois de début, dans l'ordre de la liste. */
export function groupByMonth(events: AgendaEvent[]): { key: string; events: AgendaEvent[] }[] {
  const groups: { key: string; events: AgendaEvent[] }[] = [];
  for (const event of events) {
    const key = monthKey(event.starts_at);
    const last = groups[groups.length - 1];
    if (last?.key === key) last.events.push(event);
    else groups.push({ key, events: [event] });
  }
  return groups;
}

/** Nombre de jours calendaires couverts (1 sans date de fin), dans le fuseau de l'appareil. */
export function dayCount(event: Pick<AgendaEvent, 'starts_at' | 'ends_at'>): number {
  if (!event.ends_at) return 1;
  const start = new Date(event.starts_at);
  const end = new Date(event.ends_at);
  const startDay = Date.UTC(start.getFullYear(), start.getMonth(), start.getDate());
  const endDay = Date.UTC(end.getFullYear(), end.getMonth(), end.getDate());
  return Math.max(1, Math.round((endDay - startDay) / 86_400_000) + 1);
}

/** Médiatrice présente, sur la fiche d'un événement. */
export interface EventAttendee extends Attendee {
  first_name: string;
  country: string | null;
  expertises: string[];
  role: { value: 'participant' | 'speaker'; label: string };
  /** Jours de présence (« 2026-11-10 ») ; null = tous les jours. */
  attendance_days: string[] | null;
  /** Note facultative de la médiatrice. */
  attendance_note: string | null;
  /** Créneaux où elle intervient, d'après le programme. */
  talks: { title: string; detail: string | null; starts_at: string }[];
  /** Disponible pour retrouver les autres médiatrices sur place. */
  wants_to_meet: boolean;
}

export interface ProgramSession {
  id: number;
  /** « 14:30 », dans le fuseau de l'appareil. */
  time: string;
  title: string;
  detail: string | null;
  /** Créneau propre au réseau (intervention, rencontre), mis en avant. */
  is_network: boolean;
  speaker: { name: string; slug: string; photo_url: string | null } | null;
}

export interface ProgramDay {
  /** « 2026-11-10 », dans le fuseau de l'appareil. */
  date: string;
  sessions: ProgramSession[];
}

export type EventStatus = 'upcoming' | 'ongoing' | 'past';

export const STATUS_LABELS: Record<EventStatus, string> = {
  upcoming: 'À venir',
  ongoing: 'En cours',
  past: 'Terminé',
};

/** Fiche d'un événement : toutes les médiatrices présentes et le programme. */
export interface EventDetail extends Omit<AgendaEvent, 'attendees'> {
  status: EventStatus;
  /** La personne connectée propose de se retrouver sur place. */
  wants_to_meet: boolean;
  /** Participation de la personne connectée (jours, note) ; null si elle ne participe pas. */
  attendance: Attendance | null;
  description: string | null;
  city: string | null;
  venue: string | null;
  photo_url: string | null;
  organizer: { name: string; detail: string | null } | null;
  languages: string | null;
  links: { label: string; url: string }[];
  attendees: EventAttendee[];
  program: ProgramDay[];
}

export async function fetchEvent(slug: string, signal?: AbortSignal): Promise<EventDetail> {
  const { data } = await http.get<{ data: EventDetail }>(`/events/${slug}`, {
    params: { timezone: timezone() },
    signal,
  });
  return data.data;
}

/** « 20261110T080000Z » (agendas en ligne). */
function compactUtc(date: Date): string {
  return date
    .toISOString()
    .replace(/[-:]/g, '')
    .replace(/\.\d{3}/, '');
}

/** « 20261110 », jour de l'appareil. */
function compactDay(date: Date): string {
  return todayKey(date).replace(/-/g, '');
}

/**
 * Liens « Ajouter à mon agenda » (Google, Outlook). Sans heure de fin connue, l'événement est
 * ajouté pour la journée entière plutôt qu'avec une durée inventée.
 */
export function calendarLinks(
  event: Pick<EventDetail, 'title' | 'starts_at' | 'ends_at' | 'place' | 'venue'>,
  url: string
): { google: string; outlook: string } {
  const start = new Date(event.starts_at);
  const end = event.ends_at ? new Date(event.ends_at) : null;
  const location = [event.venue, event.place].filter(Boolean).join(', ');
  const nextDay = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 1);

  const google = new URLSearchParams({
    action: 'TEMPLATE',
    text: event.title,
    dates: end
      ? `${compactUtc(start)}/${compactUtc(end)}`
      : `${compactDay(start)}/${compactDay(nextDay)}`,
    details: url,
    location,
  });

  const outlook = new URLSearchParams({
    subject: event.title,
    startdt: end ? start.toISOString() : todayKey(start),
    enddt: end ? end.toISOString() : todayKey(nextDay),
    body: url,
    location,
  });
  if (!end) outlook.set('allday', 'true');

  return {
    google: `https://calendar.google.com/calendar/render?${google.toString()}`,
    outlook: `https://outlook.live.com/calendar/0/deeplink/compose?${outlook.toString()}`,
  };
}

/** Jours couverts par un événement, dans le fuseau de l'appareil (« 2026-11-10 »…). */
export function eventDays(event: Pick<AgendaEvent, 'starts_at' | 'ends_at'>): string[] {
  const start = new Date(event.starts_at);
  const day = new Date(start.getFullYear(), start.getMonth(), start.getDate());
  return Array.from({ length: dayCount(event) }, (_, index) =>
    todayKey(new Date(day.getFullYear(), day.getMonth(), day.getDate() + index))
  );
}

function dateOf(key: string): Date {
  const [year, month, day] = key.split('-').map(Number);
  return new Date(year!, month! - 1, day!);
}

/** « 2026-11-10 » → « Mardi 10 novembre ». */
export function weekdayLabel(key: string): string {
  const label = new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  }).format(dateOf(key));
  return label.charAt(0).toUpperCase() + label.slice(1);
}

/** « le 10 novembre », « les 10 et 12 novembre », « les 10, 11 et 12 novembre ». */
export function daysPhrase(keys: string[]): string {
  const dates = [...keys].sort().map(dateOf);
  const dayMonth = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long' });
  if (dates.length === 1) return `le ${dayMonth.format(dates[0]!)}`;

  const sameMonth = dates.every((date) => date.getMonth() === dates[0]!.getMonth());
  const parts = dates.map((date, index) =>
    sameMonth && index < dates.length - 1 ? String(date.getDate()) : dayMonth.format(date)
  );
  return `les ${parts.slice(0, -1).join(', ')} et ${parts[parts.length - 1]}`;
}

/**
 * Présence d'une médiatrice sur un événement de plusieurs jours : « Présente les deux jours »,
 * « Présente tous les jours », « Présente le 10 novembre » ; null sur un événement d'un jour.
 */
export function presenceLabel(days: string[] | null, allDays: string[]): string | null {
  if (allDays.length < 2) return null;
  const present = days?.filter((day) => allDays.includes(day)) ?? allDays;
  if (present.length === 0 || present.length === allDays.length) {
    return allDays.length === 2 ? 'Présente les deux jours' : 'Présente tous les jours';
  }
  return `Présente ${daysPhrase(present)}`;
}

/** Intervention d'après le programme : « Panel « Titre », 10 nov. à 14 h 30 ». */
export function talkLabel(talk: EventAttendee['talks'][number]): string {
  const date = new Date(talk.starts_at);
  const day = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short' }).format(date);
  const minutes = date.getMinutes();
  const time = `${date.getHours()} h${minutes ? ` ${String(minutes).padStart(2, '0')}` : ''}`;
  const title = `« ${talk.title} »`;
  return `${talk.detail ? `${talk.detail} ${title}` : title}, ${day} à ${time}`;
}
