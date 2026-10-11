import { describe, expect, it } from 'vitest';

import { isFiltered, queryFromRoute, routeFromQuery } from './members';

describe('members query', () => {
  it('reads the search from the URL', () => {
    const query = queryFromRoute({
      q: 'dakar',
      expertise: ['3', '5'],
      region: 'west_africa',
      disponible: '1',
      tri: 'pays',
    });

    expect(query).toEqual({
      q: 'dakar',
      selected: {
        expertise: ['3', '5'],
        region: ['west_africa'],
        organization: [],
      },
      toggles: { available: true, upcoming: false, favorites: false },
      sort: 'country',
      page: 1,
      perPage: 12,
    });
  });

  it('reads the page from the URL and writes it only beyond the first one', () => {
    expect(queryFromRoute({ page: '3' }).page).toBe(3);
    expect(queryFromRoute({ page: '0' }).page).toBe(1);
    expect(queryFromRoute({ page: 'abc' }).page).toBe(1);
    expect(queryFromRoute({ page: '2.5' }).page).toBe(1);

    const query = queryFromRoute({ region: 'west_africa' });
    expect(routeFromQuery(query)).toEqual({ region: ['west_africa'] });
    expect(routeFromQuery({ ...query, page: 2 })).toEqual({ region: ['west_africa'], page: '2' });
  });

  it('falls back to an empty search sorted by name', () => {
    const query = queryFromRoute({ tri: 'inconnu' });

    expect(query.sort).toBe('name');
    expect(isFiltered(query)).toBe(false);
  });

  it('writes only the criteria in use to the URL, and reads them back', () => {
    const query = queryFromRoute({ organization: ['diplomacy', 'research'], tri: 'pays' });
    const route = routeFromQuery({ ...query, q: '  justice ' });

    expect(route).toEqual({ q: 'justice', organization: ['diplomacy', 'research'], tri: 'pays' });
    expect(
      queryFromRoute(route as Record<string, string | string[]>).selected.organization
    ).toEqual(['diplomacy', 'research']);
  });

  it('tells when the list is restricted', () => {
    expect(isFiltered(queryFromRoute({ q: ' ' }))).toBe(false);
    expect(isFiltered(queryFromRoute({ q: 'claire' }))).toBe(true);
    expect(isFiltered(queryFromRoute({ organization: 'diplomacy' }))).toBe(true);
    expect(isFiltered(queryFromRoute({ disponible: '1' }))).toBe(true);
    expect(isFiltered(queryFromRoute({ favoris: '1' }))).toBe(true);
  });

  it('reads the page size from the URL, 12 by default or when unknown', () => {
    expect(queryFromRoute({ par_page: '24' }).perPage).toBe(24);
    expect(queryFromRoute({ par_page: '48' }).perPage).toBe(48);
    expect(queryFromRoute({ par_page: '1000' }).perPage).toBe(12);
    expect(queryFromRoute({}).perPage).toBe(12);

    const query = queryFromRoute({ tri: 'pays' });
    expect(routeFromQuery(query)).toEqual({ tri: 'pays' });
    expect(routeFromQuery({ ...query, perPage: 24 })).toEqual({ tri: 'pays', par_page: '24' });
  });

  it('reads and writes the yes / no criteria and the sort by event in French', () => {
    const query = queryFromRoute({ evenement: '1', favoris: '1', tri: 'evenement' });

    expect(query.toggles).toEqual({ available: false, upcoming: true, favorites: true });
    expect(query.sort).toBe('event');
    expect(routeFromQuery(query)).toEqual({ evenement: '1', favoris: '1', tri: 'evenement' });
  });
});
