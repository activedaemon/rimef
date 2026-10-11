// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import AppPagination from './AppPagination.vue';

describe('AppPagination', () => {
  it('disables « Précédent » on the first page and moves to the next one', async () => {
    const { wrapper } = await mountApp(AppPagination, { props: { modelValue: 1, max: 4 } });

    const [previous, next] = wrapper.findAll('.app-pagination__step');
    expect(previous!.classes()).toContain('disabled');
    expect(wrapper.find('.app-pagination__status').text()).toBe('Page 1 sur 4');

    await next!.trigger('click');
    expect(wrapper.emitted('update:modelValue')).toEqual([[2]]);
    wrapper.unmount();
  });

  it('disables « Suivant » on the last page and goes back to the previous one', async () => {
    const { wrapper } = await mountApp(AppPagination, { props: { modelValue: 4, max: 4 } });

    const [previous, next] = wrapper.findAll('.app-pagination__step');
    expect(next!.classes()).toContain('disabled');

    await previous!.trigger('click');
    expect(wrapper.emitted('update:modelValue')).toEqual([[3]]);
    wrapper.unmount();
  });

  it('marks the current page among the numbers', async () => {
    const { wrapper } = await mountApp(AppPagination, { props: { modelValue: 2, max: 3 } });

    expect(wrapper.find('[aria-current="page"]').text()).toBe('2');
    wrapper.unmount();
  });
});
