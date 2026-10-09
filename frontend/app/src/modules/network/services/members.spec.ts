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
    });
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

  it('reads and writes the yes / no criteria and the sort by event in French', () => {
    const query = queryFromRoute({ evenement: '1', favoris: '1', tri: 'evenement' });

    expect(query.toggles).toEqual({ available: false, upcoming: true, favorites: true });
    expect(query.sort).toBe('event');
    expect(routeFromQuery(query)).toEqual({ evenement: '1', favoris: '1', tri: 'evenement' });
  });
});
