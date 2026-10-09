// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { FeaturedEvent } from '../services/home-feed';
import FeaturedEventCard from './FeaturedEventCard.vue';

// API simulée par une fonction simple : un vi.fn() qui rejette est signalé par Vitest comme
// une promesse non gérée, même quand le composant intercepte l'erreur
const api = vi.hoisted(() => ({
  calls: [] as [number, boolean][],
  fails: false,
}));

vi.mock('../services/home-feed', () => ({
  setParticipation: async (eventId: number, participating: boolean) => {
    api.calls.push([eventId, participating]);
    if (api.fails) throw new Error('réseau');
  },
}));

const EVENT: FeaturedEvent = {
  id: 3,
  title: 'Paris Peace Forum',
  dates: '12–13 novembre 2026',
  place: 'Paris, France',
  description: 'Un espace de dialogue.',
  attendees: [
    { id: 11, name: 'Fatou Ndiaye', photo: '/api/members/11/photo?v=1' },
    { id: 12, name: 'Mariam Keita', photo: null },
    { id: 13, name: 'Leila Bouzid', photo: null },
    { id: 14, name: 'Claire Dubois', photo: null },
  ],
  attendeeCount: 8,
  isParticipating: false,
};

describe('FeaturedEventCard', () => {
  beforeEach(() => {
    api.calls = [];
    api.fails = false;
  });

  it('shows the event with its attendees and the hidden count', async () => {
    const { wrapper } = await mountApp(FeaturedEventCard, { props: { event: EVENT } });

    expect(wrapper.find('h2').text()).toBe('Paris Peace Forum');
    expect(wrapper.text()).toContain('8 médiatrices du réseau seront présentes.');
    expect(wrapper.findAll('.featured-event__stack .member-avatar')).toHaveLength(4);
    expect(wrapper.text()).toContain('+4');
    wrapper.unmount();
  });

  it('registers the participation through the API', async () => {
    const { wrapper } = await mountApp(FeaturedEventCard, { props: { event: EVENT } });
    const join = () => wrapper.find('[aria-pressed]');

    await join().trigger('click');
    await flushPromises();

    expect(api.calls).toEqual([[3, true]]);
    expect(join().attributes('aria-pressed')).toBe('true');
    expect(join().text()).toContain('Je participe');
    expect(wrapper.text()).toContain('9 médiatrices du réseau seront présentes.');
    // « Vous » en premier, puis trois participantes : toujours quatre avatars
    const avatars = wrapper.findAll('.featured-event__stack .member-avatar');
    expect(avatars).toHaveLength(4);
    expect(avatars[0]!.text()).toBe('V');
    wrapper.unmount();
  });

  it('cancels the participation shown when the API refuses it', async () => {
    api.fails = true;
    const { wrapper } = await mountApp(FeaturedEventCard, { props: { event: EVENT } });

    await wrapper.find('[aria-pressed]').trigger('click');
    await flushPromises();

    expect(wrapper.find('[aria-pressed]').attributes('aria-pressed')).toBe('false');
    expect(wrapper.text()).toContain('8 médiatrices du réseau seront présentes.');
    wrapper.unmount();
  });
});
