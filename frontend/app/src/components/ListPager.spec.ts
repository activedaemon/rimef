// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import ListPager from './ListPager.vue';

const PROPS = {
  itemsLabel: 'ressources',
  total: 20,
  page: 1,
  lastPage: 3,
  perPage: 9,
  perPageOptions: [9, 18, 36] as const,
};

describe('ListPager', () => {
  it('shows the page size and the page numbers, the range being above the list', async () => {
    const { wrapper } = await mountApp(ListPager, { props: PROPS });

    expect(wrapper.find('.list-pager__range').exists()).toBe(false);
    expect(wrapper.find('nav').attributes('aria-label')).toBe('Pagination des ressources');
    expect(wrapper.find('.list-pager__size').text()).toContain('9 par page');
    expect(wrapper.find('.app-pagination').exists()).toBe(true);
    wrapper.unmount();
  });

  it('hides the page numbers on a single page, but keeps the page size', async () => {
    const { wrapper } = await mountApp(ListPager, {
      props: { ...PROPS, perPage: 36, lastPage: 1 },
    });

    expect(wrapper.find('.list-pager__size').exists()).toBe(true);
    expect(wrapper.find('.app-pagination').exists()).toBe(false);
    wrapper.unmount();
  });

  it('shows nothing when the list fits in the smallest page', async () => {
    const { wrapper } = await mountApp(ListPager, {
      props: { ...PROPS, total: 7, lastPage: 1 },
    });

    expect(wrapper.find('.list-pager').exists()).toBe(false);
    wrapper.unmount();
  });

  it('shows nothing for an empty list', async () => {
    const { wrapper } = await mountApp(ListPager, {
      props: { ...PROPS, total: 0, lastPage: 1 },
    });

    expect(wrapper.find('.list-pager').exists()).toBe(false);
    wrapper.unmount();
  });

  it('asks for another page', async () => {
    const { wrapper } = await mountApp(ListPager, { props: PROPS });

    const next = wrapper.findAll('button').find((button) => button.text().includes('Suivant'));
    await next!.trigger('click');
    expect(wrapper.emitted('update:page')?.[0]).toEqual([2]);
    wrapper.unmount();
  });
});
