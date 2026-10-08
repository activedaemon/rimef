import { describe, expect, it } from 'vitest';

import { DEFAULT_SEARCH_PLACEHOLDER, fitPlaceholder, isSearchScope } from './search';

describe('fitPlaceholder', () => {
  it('keeps a long placeholder on wide screens', () => {
    expect(fitPlaceholder(DEFAULT_SEARCH_PLACEHOLDER, 1440)).toBe(DEFAULT_SEARCH_PLACEHOLDER);
  });

  it('shortens a long placeholder in the narrow field', () => {
    expect(fitPlaceholder(DEFAULT_SEARCH_PLACEHOLDER, 1080)).toBe('Rechercher…');
  });

  it('keeps a short placeholder in the narrow field', () => {
    expect(fitPlaceholder('Rechercher une médiatrice…', 1080)).toBe('Rechercher une médiatrice…');
  });
});

describe('isSearchScope', () => {
  it('accepts the known sections only', () => {
    expect(isSearchScope('reseau')).toBe(true);
    expect(isSearchScope('inconnue')).toBe(false);
    expect(isSearchScope(['reseau'])).toBe(false);
  });
});
