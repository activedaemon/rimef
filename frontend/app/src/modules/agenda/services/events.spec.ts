import { describe, expect, it } from 'vitest';

import {
  calendarLinks,
  daysPhrase,
  eventDays,
  presenceLabel,
  talkLabel,
  dayCount,
  dayLabel,
  groupByMonth,
  isFiltered,
  monthLabel,
  queryFromRoute,
  routeFromQuery,
} from './events';
import type { AgendaEvent } from './events';

function event(id: number, startsAt: string): AgendaEvent {
  return {
    id,
    slug: `evenement-${id}`,
    title: `Événement ${id}`,
    type: null,
    starts_at: startsAt,
    ends_at: null,
    place: null,
    is_online: false,
    is_past: false,
    themes: [],
    attendee_count: 0,
    attendees: [],
    is_participating: false,
  };
}

describe('agenda query', () => {
  it('reads the tab, the filters, the month and the page from the URL', () => {
    const query = queryFromRoute({
      onglet: 'passes',
      type: ['workshop', 'summit'],
      region: 'online',
      mois: '2026-11',
      'en-ligne': '1',
      tri: 'mediatrices',
      page: '2',
    });

    expect(query).toEqual({
      period: 'past',
      q: '',
      selected: { type: ['workshop', 'summit'], region: ['online'], theme: [] },
      month: '2026-11',
      day: '',
      toggles: { favorites: false, my_zones: false, my_themes: false, online: true, crowd: false },
      sort: 'attendees',
      page: 2,
      perPage: 5,
    });
  });

  it('reads and writes the page size, 5 by default and nothing else', () => {
    expect(queryFromRoute({ par_page: '10' }).perPage).toBe(10);
    expect(queryFromRoute({ par_page: '20' }).perPage).toBe(20);
    expect(queryFromRoute({ par_page: '50' }).perPage).toBe(50);
    expect(queryFromRoute({ par_page: '1000' }).perPage).toBe(5);
    expect(queryFromRoute({}).perPage).toBe(5);
    expect(routeFromQuery({ ...queryFromRoute({}), perPage: 20 })).toEqual({ par_page: '20' });
    expect(routeFromQuery(queryFromRoute({}))).toEqual({});
  });

  it('falls back to upcoming events by date, and ignores malformed values', () => {
    const query = queryFromRoute({ onglet: 'demain', mois: 'novembre', page: '0' });

    expect(query.period).toBe('upcoming');
    expect(query.month).toBe('');
    expect(query.page).toBe(1);
    expect(isFiltered(query)).toBe(false);
  });

  it('writes only the criteria in use to the URL, and reads them back', () => {
    const query = queryFromRoute({ onglet: 'mes-evenements', theme: ['3'], affluence: '1' });
    const route = routeFromQuery({ ...query, q: '  forum ' });

    expect(route).toEqual({ onglet: 'mes-evenements', q: 'forum', theme: ['3'], affluence: '1' });
    expect(queryFromRoute(route as Record<string, string | string[]>)).toEqual({
      ...query,
      q: 'forum',
    });
  });

  it('reads and writes the day chosen in the calendar', () => {
    expect(queryFromRoute({ jour: '2026-11-12' }).day).toBe('2026-11-12');
    expect(queryFromRoute({ jour: 'demain' }).day).toBe('');
    expect(routeFromQuery({ ...queryFromRoute({}), day: '2026-11-12' })).toEqual({
      jour: '2026-11-12',
    });
    expect(isFiltered(queryFromRoute({ jour: '2026-11-12' }))).toBe(true);
    expect(dayLabel('2026-11-12')).toBe('Le 12 novembre 2026');
  });

  it('tells when the list of the tab is restricted', () => {
    expect(isFiltered(queryFromRoute({ onglet: 'passes' }))).toBe(false);
    expect(isFiltered(queryFromRoute({ mois: '2026-11' }))).toBe(true);
    expect(isFiltered(queryFromRoute({ 'mes-zones': '1' }))).toBe(true);
  });
});

describe('agenda dates', () => {
  it('groups events by month of start, in list order', () => {
    const groups = groupByMonth([
      event(1, '2026-11-12T08:00:00Z'),
      event(2, '2026-11-18T08:00:00Z'),
      event(3, '2026-12-05T08:00:00Z'),
    ]);

    expect(groups.map((group) => [group.key, group.events.map((item) => item.id)])).toEqual([
      ['2026-11', [1, 2]],
      ['2026-12', [3]],
    ]);
    expect(monthLabel('2026-11')).toBe('Novembre 2026');
  });

  it('counts the calendar days of an event', () => {
    expect(dayCount({ starts_at: '2026-11-12T08:00:00Z', ends_at: null })).toBe(1);
    expect(dayCount({ starts_at: '2026-11-12T08:00:00Z', ends_at: '2026-11-13T17:00:00Z' })).toBe(
      2
    );
  });
});

describe('calendarLinks', () => {
  const forum = {
    title: 'Paris Peace Forum',
    starts_at: '2026-11-10T08:00:00Z',
    ends_at: '2026-11-11T17:00:00Z',
    place: 'Paris, France',
    venue: 'Palais de Chaillot',
  };

  it('gives timed links when the end is known', () => {
    const links = calendarLinks(forum, 'https://rimef.org/agenda/paris-peace-forum-2026');
    const google = new URL(links.google).searchParams;
    const outlook = new URL(links.outlook).searchParams;

    expect(google.get('dates')).toBe('20261110T080000Z/20261111T170000Z');
    expect(google.get('location')).toBe('Palais de Chaillot, Paris, France');
    expect(google.get('details')).toBe('https://rimef.org/agenda/paris-peace-forum-2026');
    expect(outlook.get('startdt')).toBe('2026-11-10T08:00:00.000Z');
    expect(outlook.get('allday')).toBeNull();
  });

  it('adds the whole day when the end is unknown', () => {
    const links = calendarLinks(
      { ...forum, starts_at: '2026-10-18T12:00:00Z', ends_at: null },
      'https://rimef.org/agenda/x'
    );
    const google = new URL(links.google).searchParams;
    const outlook = new URL(links.outlook).searchParams;

    expect(google.get('dates')).toBe('20261018/20261019');
    expect(outlook.get('startdt')).toBe('2026-10-18');
    expect(outlook.get('enddt')).toBe('2026-10-19');
    expect(outlook.get('allday')).toBe('true');
  });
});

describe('presence labels', () => {
  const twoDays = ['2026-11-10', '2026-11-11'];
  const threeDays = ['2026-11-10', '2026-11-11', '2026-11-12'];

  it('says all days, some days, or nothing on a one-day event', () => {
    expect(presenceLabel(null, twoDays)).toBe('Présente les deux jours');
    expect(presenceLabel(null, threeDays)).toBe('Présente tous les jours');
    expect(presenceLabel(['2026-11-10'], twoDays)).toBe('Présente le 10 novembre');
    expect(presenceLabel(['2026-11-12', '2026-11-10'], threeDays)).toBe(
      'Présente les 10 et 12 novembre'
    );
    expect(presenceLabel(null, ['2026-11-10'])).toBeNull();
  });

  it('writes several days across months', () => {
    expect(daysPhrase(['2026-11-30', '2026-12-01', '2026-12-02'])).toBe(
      'les 30 novembre, 1 décembre et 2 décembre'
    );
    expect(daysPhrase(['2026-11-10', '2026-11-11', '2026-11-12'])).toBe(
      'les 10, 11 et 12 novembre'
    );
  });

  it('lists the days of an event', () => {
    expect(
      eventDays({ starts_at: '2026-11-10T12:00:00Z', ends_at: '2026-11-12T12:00:00Z' })
    ).toEqual(threeDays);
  });

  it('describes a talk from the program', () => {
    const start = new Date(2026, 10, 10, 14, 30).toISOString();
    expect(talkLabel({ title: 'Femmes médiatrices', detail: 'Panel', starts_at: start })).toBe(
      'Panel « Femmes médiatrices », 10 nov. à 14 h 30'
    );
    const nine = new Date(2026, 10, 10, 9, 0).toISOString();
    expect(talkLabel({ title: 'Ouverture', detail: null, starts_at: nine })).toBe(
      '« Ouverture », 10 nov. à 9 h'
    );
  });
});
