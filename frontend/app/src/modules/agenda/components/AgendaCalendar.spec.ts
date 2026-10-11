// @vitest-environment happy-dom
import { describe, expect, it, vi } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import AgendaCalendar from './AgendaCalendar.vue';

vi.mock('../services/events', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/events')>()),
  fetchCalendar: vi.fn(async () => [
    { date: '2026-11-12', events: [{ id: 3, title: 'Paris Peace Forum' }] },
    { date: '2026-11-13', events: [{ id: 3, title: 'Paris Peace Forum' }] },
  ]),
}));

describe('AgendaCalendar', () => {
  it('shows the month and lets only the days with an event be chosen', async () => {
    const { wrapper } = await mountApp(AgendaCalendar, {
      props: { modelValue: '', focusMonth: '2026-11' },
    });
    await vi.waitFor(() => expect(wrapper.findAll('.q-date__calendar-item--in')).toHaveLength(2));

    expect(wrapper.find('.agenda-calendar__title').text()).toBe('Novembre 2026');
    await wrapper.find('.q-date__calendar-item--in .q-btn').trigger('click');
    expect(wrapper.emitted('update:modelValue')).toEqual([['2026-11-12']]);
    wrapper.unmount();
  });
});
