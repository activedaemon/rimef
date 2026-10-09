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
        language: [],
        organization: [],
      },
      available: true,
      sort: 'country',
    });
  });

  it('falls back to an empty search sorted by name', () => {
    const query = queryFromRoute({ tri: 'inconnu' });

    expect(query.sort).toBe('name');
    expect(isFiltered(query)).toBe(false);
  });

  it('writes only the criteria in use to the URL, and reads them back', () => {
    const query = queryFromRoute({ language: ['fr', 'wo'], tri: 'pays' });
    const route = routeFromQuery({ ...query, q: '  justice ' });

    expect(route).toEqual({ q: 'justice', language: ['fr', 'wo'], tri: 'pays' });
    expect(queryFromRoute(route as Record<string, string | string[]>).selected.language).toEqual([
      'fr',
      'wo',
    ]);
  });

  it('tells when the list is restricted', () => {
    expect(isFiltered(queryFromRoute({ q: ' ' }))).toBe(false);
    expect(isFiltered(queryFromRoute({ q: 'claire' }))).toBe(true);
    expect(isFiltered(queryFromRoute({ organization: 'diplomacy' }))).toBe(true);
    expect(isFiltered(queryFromRoute({ disponible: '1' }))).toBe(true);
  });
});
