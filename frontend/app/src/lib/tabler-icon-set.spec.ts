import { describe, expect, it } from 'vitest';

import { tablerIconMapFn } from './tabler-icon-set';

describe('tablerIconMapFn', () => {
  it('maps a Tabler name and the internal Material names of Quasar', () => {
    expect(tablerIconMapFn('search')).toEqual({ cls: 'ti ti-search' });
    expect(tablerIconMapFn('close')).toEqual({ cls: 'ti ti-x' });
    expect(tablerIconMapFn('ti ti-users')).toEqual({ cls: 'ti ti-users' });
  });

  it('leaves SVG paths, images and sprites to Quasar', () => {
    expect(tablerIconMapFn('M18 7v14l-6 -4l-6 4v-14|0 0 24 24')).toBeUndefined();
    expect(tablerIconMapFn('img:/logo.svg')).toBeUndefined();
    expect(tablerIconMapFn('svguse:/icons.svg#leaf')).toBeUndefined();
  });
});
