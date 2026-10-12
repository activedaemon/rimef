// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import FilterOptions from './FilterOptions.vue';

const OPTIONS = [
  { value: 'west_africa', label: 'Afrique de l’Ouest', count: 7 },
  { value: 'europe', label: 'Europe', count: 6 },
];

describe('FilterOptions', () => {
  it('applies a ticked or unticked value at once', async () => {
    const { wrapper } = await mountApp(FilterOptions, {
      props: { options: OPTIONS, modelValue: ['europe'] },
    });

    const boxes = wrapper.findAll('.q-checkbox');
    await boxes[0]!.trigger('click');
    await boxes[1]!.trigger('click');

    expect(wrapper.emitted('update:modelValue')).toEqual([[['europe', 'west_africa']], [['west_africa']]]);
    wrapper.unmount();
  });

  it('has no footer: the menu closes by clicking elsewhere', async () => {
    const { wrapper } = await mountApp(FilterOptions, {
      props: { options: OPTIONS, modelValue: [] },
    });

    expect(wrapper.find('.q-btn').exists()).toBe(false);
    wrapper.unmount();
  });
});
