import { describe, expect, it } from 'vitest';

import {
  calendarLinks,
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
    });
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
