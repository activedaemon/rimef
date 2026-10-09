// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import NetworkNewsCard from './NetworkNewsCard.vue';

describe('NetworkNewsCard', () => {
  it('links each new mediator to her profile', async () => {
    const { wrapper } = await mountApp(NetworkNewsCard, {
      props: {
        news: [
          {
            person: { id: 30, name: 'Esther Omam', photo: '/api/members/30/photo?v=1' },
            action: 'a rejoint le réseau',
            when: 'Aujourd’hui',
          },
        ],
      },
    });

    expect(wrapper.find('.news__name').attributes('href')).toBe('/reseau/30');
    expect(wrapper.find('.news').text()).toContain('Esther Omam a rejoint le réseau');
    expect(wrapper.find('img').attributes('src')).toBe('/api/members/30/photo?v=1');
    wrapper.unmount();
  });

  it('says so when there is no news', async () => {
    const { wrapper } = await mountApp(NetworkNewsCard, { props: { news: [] } });

    expect(wrapper.text()).toContain('Aucune nouveauté pour le moment.');
    wrapper.unmount();
  });
});
