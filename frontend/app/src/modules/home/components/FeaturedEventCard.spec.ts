// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { FeaturedEvent } from '../services/home-feed';
import FeaturedEventCard from './FeaturedEventCard.vue';

const EVENT: FeaturedEvent = {
  id: 'e1',
  title: 'Paris Peace Forum',
  dates: '12–13 novembre 2026',
  place: 'Paris, France',
  description: 'Un espace de dialogue.',
  attendees: ['Fatou Ndiaye', 'Mariam Keita', 'Leila Bouzid', 'Claire Dubois'],
  attendeeCount: 8,
};

describe('FeaturedEventCard', () => {
  it('shows the event with its attendees and the hidden count', async () => {
    const { wrapper } = await mountApp(FeaturedEventCard, { props: { event: EVENT } });

    expect(wrapper.find('h2').text()).toBe('Paris Peace Forum');
    expect(wrapper.text()).toContain('8 médiatrices du réseau seront présentes.');
    expect(wrapper.text()).toContain('+4');
    wrapper.unmount();
  });

  it('toggles the participation locally', async () => {
    const { wrapper } = await mountApp(FeaturedEventCard, { props: { event: EVENT } });
    const join = () => wrapper.find('[aria-pressed]');

    await join().trigger('click');
    expect(join().attributes('aria-pressed')).toBe('true');
    expect(join().text()).toContain('Je participe');
    expect(wrapper.text()).toContain('9 médiatrices du réseau seront présentes.');

    await join().trigger('click');
    expect(join().attributes('aria-pressed')).toBe('false');
    expect(wrapper.text()).toContain('8 médiatrices du réseau seront présentes.');
    wrapper.unmount();
  });
});
