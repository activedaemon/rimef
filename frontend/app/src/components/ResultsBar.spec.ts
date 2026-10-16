// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import ResultsBar from './ResultsBar.vue';

const BASE = {
  noun: { one: 'médiatrice', many: 'médiatrices' },
  from: 13,
  to: 24,
  total: 32,
  lastPage: 3,
  filtered: false,
  overall: 32,
  sortOptions: [{ value: 'name', label: 'Nom' }],
  sort: 'name',
};

const count = (wrapper: { find: (selector: string) => { text: () => string } }) =>
  wrapper.find('.results-bar__count').text().replace(/\s+/g, ' ');

describe('ResultsBar', () => {
  it('shows the range on several pages, read as « 13 à 24 »', async () => {
    const { wrapper } = await mountApp(ResultsBar, { props: BASE });

    expect(count(wrapper)).toContain('Médiatrices 13–24');
    expect(wrapper.find('.results-bar__count b').attributes('aria-hidden')).toBe('true');
    expect(wrapper.find('.results-bar__count .sr-only').text()).toBe('13 à 24');
    expect(count(wrapper)).toContain('sur 32');
    wrapper.unmount();
  });

  it('shows a plain count on a single page, in the singular when needed', async () => {
    const { wrapper } = await mountApp(ResultsBar, {
      props: { ...BASE, from: 1, to: 1, total: 1, lastPage: 1, filtered: true },
    });

    expect(count(wrapper)).toContain('1 médiatrice');
    expect(count(wrapper)).toContain('32 médiatrices au total');
    wrapper.unmount();
  });

  it('uses the page complement instead of « au total »', async () => {
    const { wrapper } = await mountApp(ResultsBar, {
      props: {
        ...BASE,
        noun: { one: 'événement', many: 'événements' },
        from: 1,
        to: 5,
        total: 5,
        lastPage: 1,
        filtered: true,
        overall: 21,
        suffix: 'à venir',
      },
    });

    expect(count(wrapper)).toContain('5 événements');
    expect(count(wrapper)).toContain('21 événements à venir');
    wrapper.unmount();
  });

  it('shows the grid and list switch only when the page offers it', async () => {
    const without = await mountApp(ResultsBar, { props: BASE });
    expect(without.wrapper.find('.results-bar__view').exists()).toBe(false);
    without.wrapper.unmount();

    const { wrapper } = await mountApp(ResultsBar, { props: { ...BASE, view: 'list' } });
    const labels = wrapper
      .findAll('.results-bar__view .q-btn')
      .map((button) => button.attributes('aria-label'));
    expect(labels).toEqual(['Grille', 'Liste']);
    wrapper.unmount();
  });
});
