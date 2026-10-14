// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import * as events from '../services/events';
import ParticipationDialog from '../components/ParticipationDialog.vue';
import AgendaView from './AgendaView.vue';

vi.mock('../services/events', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/events')>()),
  fetchEvents: vi.fn(),
  fetchEventFilters: vi.fn(),
  fetchCalendar: vi.fn(),
}));

const EMPTY_PAGE: events.EventPage = {
  data: [],
  meta: { current_page: 1, last_page: 1, total: 0, from: null, to: null },
};

async function openAgenda(path: string) {
  const result = await mountApp(AgendaView, {
    path,
    global: { stubs: { QPage: { template: '<div><slot /></div>' } } },
  });
  await flushPromises();
  return result;
}

describe('AgendaView', () => {
  beforeEach(() => {
    vi.mocked(events.fetchEvents).mockReset().mockResolvedValue(structuredClone(EMPTY_PAGE));
    vi.mocked(events.fetchEventFilters).mockReset().mockRejectedValue(new Error('hors ligne'));
    vi.mocked(events.fetchCalendar).mockReset().mockResolvedValue([]);
  });

  it('invites to take part in an event when « Mes événements » is empty', async () => {
    const { wrapper } = await openAgenda('/agenda?onglet=mes-evenements');

    expect(wrapper.find('.empty-state__title').text()).toBe(
      'Vous ne participez à aucun événement pour le moment'
    );
    expect(wrapper.find('.empty-state button').exists()).toBe(false);
    wrapper.unmount();
  });

  it('keeps the generic message when a filter restricts « Mes événements »', async () => {
    const { wrapper } = await openAgenda('/agenda?onglet=mes-evenements&type=workshop');

    expect(wrapper.find('.empty-state__title').text()).toBe('Aucun événement ne correspond');
    expect(wrapper.text()).toContain('Réinitialiser les filtres');
    wrapper.unmount();
  });

  it('keeps the generic message on the other tabs', async () => {
    const { wrapper } = await openAgenda('/agenda');

    expect(wrapper.find('.empty-state__title').text()).toBe('Aucun événement ne correspond');
    wrapper.unmount();
  });

  it('takes an event out of « Mes événements » when she no longer takes part', async () => {
    const forum: events.AgendaEvent = {
      id: 3,
      slug: 'paris-peace-forum-2026',
      title: 'Paris Peace Forum',
      type: null,
      starts_at: '2026-11-12T08:00:00Z',
      ends_at: null,
      place: 'Paris, France',
      is_online: false,
      is_past: false,
      themes: [],
      attendee_count: 1,
      attendees: [],
      is_participating: true,
    };
    vi.mocked(events.fetchEvents).mockResolvedValueOnce({
      data: [forum],
      meta: { current_page: 1, last_page: 1, total: 1, from: 1, to: 1 },
    });
    const { wrapper } = await openAgenda('/agenda?onglet=mes-evenements');
    expect(wrapper.findAll('.event-row')).toHaveLength(1);

    await wrapper.find('.event-row__join').trigger('click');
    wrapper.findComponent(ParticipationDialog).vm.$emit('saved', false);
    await flushPromises();

    expect(events.fetchEvents).toHaveBeenCalledTimes(2);
    expect(wrapper.findAll('.event-row')).toHaveLength(0);
    expect(wrapper.find('.empty-state__title').text()).toBe(
      'Vous ne participez à aucun événement pour le moment'
    );
    wrapper.unmount();
  });
});
