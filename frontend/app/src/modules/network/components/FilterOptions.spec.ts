// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import FilterOptions from './FilterOptions.vue';

const OPTIONS = [
  { value: 'west_africa', label: 'Afrique de l’Ouest', count: 7 },
  { value: 'europe', label: 'Europe', count: 6 },
  { value: 'north_africa', label: 'Afrique du Nord', count: 3 },
];

describe('FilterOptions', () => {
  it('applies the ticked values only with « Afficher les résultats »', async () => {
    const { wrapper } = await mountApp(FilterOptions, {
      props: { options: OPTIONS, selected: ['europe'] },
    });

    const boxes = wrapper.findAll('.q-checkbox');
    await boxes[0]!.trigger('click');
    await boxes[2]!.trigger('click');
    expect(wrapper.emitted('apply')).toBeUndefined();

    await wrapper.find('.filter-options__apply').trigger('click');
    expect(wrapper.emitted('apply')).toEqual([[['europe', 'west_africa', 'north_africa']]]);
    wrapper.unmount();
  });

  it('clears the draft without applying it', async () => {
    const { wrapper } = await mountApp(FilterOptions, {
      props: { options: OPTIONS, selected: ['europe', 'west_africa'] },
    });

    await wrapper.find('.link-more').trigger('click');
    expect(wrapper.emitted('apply')).toBeUndefined();
    expect(wrapper.findAll('.q-checkbox[aria-checked="true"]')).toHaveLength(0);

    await wrapper.find('.filter-options__apply').trigger('click');
    expect(wrapper.emitted('apply')).toEqual([[[]]]);
    wrapper.unmount();
  });
});
