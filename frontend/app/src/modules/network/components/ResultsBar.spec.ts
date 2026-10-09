// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import ResultsBar from './ResultsBar.vue';

const MODELS = { view: 'grid', sort: 'name' } as const;

describe('ResultsBar', () => {
  it('shows the size of the directory when nothing is filtered', async () => {
    const { wrapper } = await mountApp(ResultsBar, {
      props: { ...MODELS, count: 124, directoryTotal: 124, filtered: false },
    });

    expect(wrapper.find('.results-bar__count').text()).toBe('124 médiatrices');
    expect(wrapper.find('.results-bar__reset').exists()).toBe(false);
    wrapper.unmount();
  });

  it('shows the filtered count against the directory and offers to clear', async () => {
    const { wrapper } = await mountApp(ResultsBar, {
      props: { ...MODELS, count: 1, directoryTotal: 124, filtered: true },
    });

    expect(wrapper.find('.results-bar__count').text()).toContain('1 médiatrice sur 124');
    await wrapper.find('.results-bar__reset').trigger('click');
    expect(wrapper.emitted('reset')).toHaveLength(1);
    wrapper.unmount();
  });

  it('labels the grid and list buttons', async () => {
    const { wrapper } = await mountApp(ResultsBar, {
      props: { ...MODELS, count: 3, directoryTotal: 3, filtered: false },
    });

    const labels = wrapper
      .findAll('.results-bar__view .q-btn')
      .map((button) => button.attributes('aria-label'));
    expect(labels).toEqual(['Grille', 'Liste']);
    wrapper.unmount();
  });
});
