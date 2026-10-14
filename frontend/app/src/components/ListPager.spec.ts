// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import ListPager from './ListPager.vue';

const PROPS = {
  noun: 'Ressources',
  itemsLabel: 'ressources',
  total: 20,
  from: 1,
  to: 9,
  page: 1,
  lastPage: 3,
  perPage: 9,
  perPageOptions: [9, 18, 36] as const,
};

describe('ListPager', () => {
  it('shows the range, the page size and the page numbers', async () => {
    const { wrapper } = await mountApp(ListPager, { props: PROPS });

    expect(wrapper.find('.list-pager__range').text()).toBe('Ressources 1–9 sur 20');
    expect(wrapper.find('nav').attributes('aria-label')).toBe('Pagination des ressources');
    expect(wrapper.find('.list-pager__size').text()).toContain('9 par page');
    expect(wrapper.find('.app-pagination').exists()).toBe(true);
    wrapper.unmount();
  });

  it('hides the page numbers on a single page, but keeps the page size', async () => {
    const { wrapper } = await mountApp(ListPager, {
      props: { ...PROPS, to: 20, perPage: 36, lastPage: 1 },
    });

    expect(wrapper.find('.list-pager__size').exists()).toBe(true);
    expect(wrapper.find('.app-pagination').exists()).toBe(false);
    wrapper.unmount();
  });

  it('shows only the range when the list fits in the smallest page', async () => {
    const { wrapper } = await mountApp(ListPager, {
      props: { ...PROPS, total: 7, to: 7, lastPage: 1 },
    });

    expect(wrapper.find('.list-pager__range').text()).toBe('Ressources 1–7 sur 7');
    expect(wrapper.find('nav').exists()).toBe(false);
    wrapper.unmount();
  });

  it('shows nothing for an empty list', async () => {
    const { wrapper } = await mountApp(ListPager, {
      props: { ...PROPS, total: 0, from: 0, to: 0, lastPage: 1 },
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
