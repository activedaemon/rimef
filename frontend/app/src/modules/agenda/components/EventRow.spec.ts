// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { AgendaEvent } from '../services/events';
import EventRow from './EventRow.vue';

const FORUM: AgendaEvent = {
  id: 3,
  slug: 'paris-peace-forum-2026',
  title: 'Paris Peace Forum',
  type: { value: 'international_forum', label: 'Forum international' },
  starts_at: '2026-11-12T08:00:00Z',
  ends_at: '2026-11-13T17:00:00Z',
  place: 'Paris, France',
  is_online: false,
  is_past: false,
  themes: ['Multilatéralisme'],
  attendee_count: 9,
  attendees: [
    { id: 1, slug: 'aminata-diallo', name: 'Aminata Diallo', photo_url: null },
    { id: 2, slug: 'claire-dubois', name: 'Claire Dubois', photo_url: null },
    { id: 3, slug: 'fatou-ndiaye', name: 'Fatou Ndiaye', photo_url: null },
  ],
  is_participating: false,
};

describe('EventRow', () => {
  it('shows the date, title, place, type and the mediators who attend', async () => {
    const { wrapper } = await mountApp(EventRow, { props: { event: FORUM } });

    expect(wrapper.find('.date-tile__day').text()).toBe('12');
    expect(wrapper.find('.date-tile__note').text()).toBe('2 jours');
    expect(wrapper.find('h3 a').attributes('href')).toBe('/agenda/paris-peace-forum-2026');
    expect(wrapper.text()).toContain('Paris, France');
    expect(wrapper.text()).toContain('Forum international');
    expect(wrapper.find('.event-row__more').text()).toBe('+6');
    expect(wrapper.find('.event-row__people').text()).toContain('9 médiatrices participent');
    wrapper.unmount();
  });

  it('asks to toggle the participation, and shows it once confirmed', async () => {
    const { wrapper } = await mountApp(EventRow, { props: { event: FORUM } });

    await wrapper.find('.event-row__join').trigger('click');
    expect(wrapper.emitted('toggleParticipation')).toHaveLength(1);

    await wrapper.setProps({ event: { ...FORUM, is_participating: true, attendee_count: 10 } });
    expect(wrapper.find('.event-row__join').text()).toContain('Je participe');
    expect(wrapper.find('.event-row--mine').exists()).toBe(true);
    expect(wrapper.find('.event-row__check').exists()).toBe(true);
    expect(wrapper.find('.sr-only').text()).toBe('Vous participez');
    expect(wrapper.text()).toContain('10 médiatrices participent');
    wrapper.unmount();
  });

  it('has no participation button on a past event', async () => {
    const { wrapper } = await mountApp(EventRow, {
      props: { event: { ...FORUM, is_past: true, is_participating: true, attendee_count: 1 } },
    });

    expect(wrapper.find('.event-row__join').exists()).toBe(false);
    expect(wrapper.find('.date-tile--past').exists()).toBe(true);
    expect(wrapper.find('.sr-only').text()).toBe('Vous y étiez');
    expect(wrapper.text()).toContain('1 médiatrice y a participé');
    wrapper.unmount();
  });
});
