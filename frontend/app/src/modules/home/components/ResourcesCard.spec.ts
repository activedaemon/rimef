// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { HomeResource } from '../services/home-feed';
import ResourcesCard from './ResourcesCard.vue';

const REPORT: HomeResource = {
  id: 1,
  slug: 'women-mediators',
  title: 'Women Mediators',
  type: { value: 'report', label: 'Rapport', action: 'Lire' },
  source: 'Conciliation Resources',
  published_on: '2026-04-14',
  duration_minutes: null,
};

describe('ResourcesCard', () => {
  it('lists the latest resources, each leading to its page, with the whole library linked', async () => {
    const podcast = {
      ...REPORT,
      id: 2,
      slug: 'paroles',
      title: 'Paroles de médiatrices',
      type: { value: 'podcast', label: 'Podcast', action: 'Écouter' },
      duration_minutes: 34,
    };
    const { wrapper } = await mountApp(ResourcesCard, { props: { resources: [REPORT, podcast] } });

    const rows = wrapper.findAll('.home-resource');
    expect(rows).toHaveLength(2);
    expect(rows[0]!.find('h3 a').attributes('href')).toBe('/ressources/women-mediators');
    expect(rows[0]!.find('.home-resource__type').text()).toBe('Rapport · Avril 2026');
    expect(rows[0]!.find('.home-resource__play').exists()).toBe(false);
    expect(rows[1]!.find('.home-resource__type').text()).toBe('Podcast · 34 min');
    // Pastille de lecture sur la vignette d'un podcast
    expect(rows[1]!.find('.home-resource__play').exists()).toBe(true);
    expect(wrapper.find('.link-more').attributes('href')).toBe('/ressources');
    wrapper.unmount();
  });

  it('says when the library is empty', async () => {
    const { wrapper } = await mountApp(ResourcesCard, { props: { resources: [] } });

    expect(wrapper.find('.home-block__empty').text()).toBe('Aucune ressource pour le moment.');
    wrapper.unmount();
  });
});
