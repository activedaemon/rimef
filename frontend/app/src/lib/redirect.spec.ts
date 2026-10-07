import { describe, expect, it } from 'vitest';

import { safeRedirect } from './redirect';

describe('safeRedirect', () => {
  it('keeps an internal path with its query', () => {
    expect(safeRedirect('/reseau?pays=SN')).toBe('/reseau?pays=SN');
  });

  it.each([
    ['an external URL', 'https://site-piege.example'],
    ['a protocol-relative URL', '//site-piege.example'],
    ['a backslash trick', '/\\site-piege.example'],
    ['a relative path', 'reseau'],
    ['a missing value', undefined],
    ['a repeated parameter', ['/reseau', '/agenda']],
  ])('falls back to the home page for %s', (_case, value) => {
    expect(safeRedirect(value)).toBe('/');
  });
});
