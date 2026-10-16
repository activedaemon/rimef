// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import ActiveFilters from './ActiveFilters.vue';

describe('ActiveFilters', () => {
  it('stays in the page even without any filter, and offers nothing to clear', async () => {
    const { wrapper } = await mountApp(ActiveFilters, { props: { filtered: false } });

    expect(wrapper.find('.active-filters').exists()).toBe(true);
    expect(wrapper.find('[aria-live="polite"]').exists()).toBe(true);
    expect(wrapper.find('.active-filters__clear').exists()).toBe(false);
    wrapper.unmount();
  });

  it('ends the filters with « Retirer les filtres », outside the announced zone', async () => {
    const { wrapper } = await mountApp(ActiveFilters, { props: { filtered: true } });

    const clear = wrapper.find('.active-filters__clear');
    expect(clear.text()).toBe('Retirer les filtres');
    expect(wrapper.find('[aria-live] .active-filters__clear').exists()).toBe(false);
    await clear.trigger('click');
    expect(wrapper.emitted('clear')).toHaveLength(1);
    wrapper.unmount();
  });
});
