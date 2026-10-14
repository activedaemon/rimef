// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { ResourceDetail } from '../services/resources';
import ResourceLinks from './ResourceLinks.vue';

const REPORT: ResourceDetail = {
  id: 1,
  slug: 'women-mediators',
  title: 'Women Mediators',
  type: { value: 'report', label: 'Rapport', action: 'Lire' },
  source: 'Conciliation Resources',
  author: null,
  published_on: '2026-04-14',
  duration_minutes: null,
  url: null,
  themes: [],
  is_saved: false,
  summary: null,
  pages: 42,
  languages: ['Français'],
  region: 'Monde',
  organization: null,
  mediators: [
    {
      id: 7,
      slug: 'aminata-diallo',
      name: 'Aminata Diallo',
      first_name: 'Aminata',
      photo_url: null,
      country: null,
      expertise: null,
      role: { value: 'contributor', label: 'Contributrice' },
      note: null,
      quote: null,
    },
    {
      id: 9,
      slug: 'fatou-ndiaye',
      name: 'Fatou Ndiaye',
      first_name: 'Fatou',
      photo_url: null,
      country: null,
      expertise: null,
      role: null,
      note: null,
      quote: 'Un rapport précieux.',
    },
  ],
  event: {
    id: 3,
    slug: 'paris-peace-forum-2026',
    title: 'Paris Peace Forum',
    starts_at: '2026-11-10T08:00:00+00:00',
    ends_at: '2026-11-11T17:00:00+00:00',
    place: 'Paris, France',
    note: 'Présenté au panel du forum.',
    attendee_count: 8,
    attendees: [],
  },
  related: [],
};

describe('ResourceLinks', () => {
  it('chains the resource, the mediators and the event, each with its link', async () => {
    const { wrapper } = await mountApp(ResourceLinks, { props: { resource: REPORT } });

    expect(wrapper.find('h2').text()).toBe('Un rapport, des médiatrices, une rencontre');
    const nodes = wrapper.findAll('.resource-links__node');
    expect(nodes).toHaveLength(3);
    expect(nodes[0]!.text()).toContain('Ce rapport');
    expect(nodes[1]!.text()).toContain('Aminata Diallo, Fatou Ndiaye');
    expect(nodes[1]!.find('.resource-links__rel').text()).toBe(
      'Aminata y a contribué, Fatou le recommande au réseau.'
    );
    expect(nodes[2]!.text()).toContain('Paris Peace Forum');
    expect(nodes[2]!.text()).toContain('Présenté au panel du forum.');

    await nodes[2]!.find('button').trigger('click');
    expect(wrapper.emitted('go')?.[0]).toEqual(['evenement']);
    wrapper.unmount();
  });

  it('agrees the title with the type and leaves out the missing links', async () => {
    const { wrapper } = await mountApp(ResourceLinks, {
      props: {
        resource: {
          ...REPORT,
          type: { value: 'analysis', label: 'Analyse', action: 'Lire' },
          event: null,
        },
      },
    });

    expect(wrapper.find('h2').text()).toBe('Une analyse et des médiatrices');
    expect(wrapper.findAll('.resource-links__node')).toHaveLength(2);
    expect(wrapper.find('.resource-links__rel').text()).toBe(
      'Aminata y a contribué, Fatou la recommande au réseau.'
    );
    wrapper.unmount();
  });
});
