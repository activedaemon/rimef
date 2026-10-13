import { describe, expect, it } from 'vitest';

import { isFiltered, publishedLabel, queryFromRoute, routeFromQuery, themeTone } from './resources';

describe('resource query', () => {
  it('reads the filters, the date, the criteria, the sort and the page from the URL', () => {
    const query = queryFromRoute({
      q: 'paix',
      type: ['podcast', 'video'],
      theme: '3',
      language: 'fr',
      date: '2025',
      enregistrees: '1',
      tri: 'titre',
      page: '2',
      par_page: '24',
    });

    expect(query).toEqual({
      q: 'paix',
      selected: { type: ['podcast', 'video'], theme: ['3'], region: [], language: ['fr'] },
      date: '2025',
      toggles: { linked: false, by_member: false, with_event: false, saved: true },
      sort: 'title',
      page: 2,
      perPage: 24,
    });
  });

  it('falls back to the most recent, 12 per page, and ignores malformed values', () => {
    const query = queryFromRoute({ tri: 'hasard', date: 'hier', page: '0', par_page: '1000' });

    expect(query.sort).toBe('recent');
    expect(query.date).toBe('');
    expect(query.page).toBe(1);
    expect(query.perPage).toBe(12);
    expect(isFiltered(query)).toBe(false);
  });

  it('writes only the criteria in use to the URL, and reads them back', () => {
    const query = queryFromRoute({
      region: ['world'],
      date: 'recent',
      reseau: '1',
      tri: 'anciennes',
    });
    const route = routeFromQuery({ ...query, q: '  guide ', perPage: 48 });

    expect(route).toEqual({
      q: 'guide',
      region: ['world'],
      date: 'recent',
      reseau: '1',
      tri: 'anciennes',
      par_page: '48',
    });
    expect(queryFromRoute(route as Record<string, string | string[]>)).toEqual({
      ...query,
      q: 'guide',
      perPage: 48,
    });
    expect(routeFromQuery(queryFromRoute({}))).toEqual({});
  });

  it('tells when the library is restricted', () => {
    expect(isFiltered(queryFromRoute({ tri: 'titre', par_page: '24' }))).toBe(false);
    expect(isFiltered(queryFromRoute({ type: 'tool' }))).toBe(true);
    expect(isFiltered(queryFromRoute({ date: '2026' }))).toBe(true);
    expect(isFiltered(queryFromRoute({ evenement: '1' }))).toBe(true);
  });
});

describe('resource labels', () => {
  it('writes the month of publication', () => {
    expect(publishedLabel('2026-04-14')).toBe('Avril 2026');
    expect(publishedLabel('2025-12-09')).toBe('Décembre 2025');
  });

  it('gives each theme a fixed tone', () => {
    expect(themeTone(4)).toBe('saffron');
    expect(themeTone(5)).toBe('petrol');
    expect(themeTone(6)).toBe('sage');
    expect(themeTone(7)).toBe('terra');
    expect(themeTone(8)).toBe(themeTone(4));
  });
});
