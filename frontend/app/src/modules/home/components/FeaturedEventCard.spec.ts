// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { FeaturedEvent } from '../services/home-feed';
import FeaturedEventCard from './FeaturedEventCard.vue';

// Fenêtre « Ma participation » simulée : elle reçoit l'événement et annonce l'enregistrement
const DialogStub = {
  props: ['modelValue', 'event', 'participating'],
  emits: ['saved'],
  template:
    '<div v-if="modelValue" class="dialog-stub" :data-starts="event.starts_at">' +
    '<button class="dialog-stub__save" @click="$emit(\'saved\', !participating)" /></div>',
};
const stubs = { global: { stubs: { ParticipationDialog: DialogStub } } };

const EVENT: FeaturedEvent = {
  id: 3,
  slug: 'paris-peace-forum-2026',
  title: 'Paris Peace Forum',
  dates: '12–13 novembre 2026',
  startsAt: '2026-11-12T08:00:00Z',
  endsAt: '2026-11-13T17:00:00Z',
  place: 'Paris, France',
  summary: 'Un espace de dialogue.',
  photo: null,
  attendees: [
    { id: 11, slug: 'fatou-ndiaye', name: 'Fatou Ndiaye', photo: '/api/members/11/photo?v=1' },
    { id: 12, slug: 'mariam-keita', name: 'Mariam Keita', photo: null },
    { id: 13, slug: 'leila-bouzid', name: 'Leila Bouzid', photo: null },
    { id: 14, slug: 'claire-dubois', name: 'Claire Dubois', photo: null },
  ],
  attendeeCount: 8,
  isParticipating: false,
};

describe('FeaturedEventCard', () => {
  it('shows the event with its attendees and the hidden count', async () => {
    const { wrapper } = await mountApp(FeaturedEventCard, { props: { event: EVENT } });

    expect(wrapper.find('h2').text()).toBe('Paris Peace Forum');
    expect(wrapper.find('.featured-event__summary').text()).toBe('Un espace de dialogue.');
    expect(wrapper.text()).toContain('8 médiatrices du réseau seront présentes.');
    expect(wrapper.findAll('.featured-event__stack .member-avatar')).toHaveLength(4);
    expect(wrapper.text()).toContain('+4');
    wrapper.unmount();
  });

  it('shows the photo of the event, the neutral visual without it', async () => {
    const { wrapper } = await mountApp(FeaturedEventCard, {
      props: { event: { ...EVENT, photo: '/api/events/4/photo?v=1' } },
    });
    expect(wrapper.find('.featured-event__visual img').attributes('src')).toBe(
      '/api/events/4/photo?v=1'
    );
    wrapper.unmount();

    const { wrapper: neutral } = await mountApp(FeaturedEventCard, { props: { event: EVENT } });
    expect(neutral.find('.featured-event__visual img').exists()).toBe(false);
    neutral.unmount();
  });

  it('opens the participation window, then shows the participation saved', async () => {
    const { wrapper } = await mountApp(FeaturedEventCard, { props: { event: EVENT }, ...stubs });
    const join = () => wrapper.find('[aria-pressed]');

    await join().trigger('click');
    expect(wrapper.find('.dialog-stub').attributes('data-starts')).toBe('2026-11-12T08:00:00Z');
    await wrapper.find('.dialog-stub__save').trigger('click');
    await flushPromises();

    expect(join().attributes('aria-pressed')).toBe('true');
    expect(join().text()).toContain('Je participe');
    expect(wrapper.text()).toContain('9 médiatrices du réseau seront présentes.');
    // « Vous » en premier, puis trois participantes : toujours quatre avatars
    const avatars = wrapper.findAll('.featured-event__stack .member-avatar');
    expect(avatars).toHaveLength(4);
    expect(avatars[0]!.text()).toBe('V');
    wrapper.unmount();
  });
});
