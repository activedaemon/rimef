// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useSession } from '@/stores/session';
import { mountApp } from '@/testing/mount-app';
import * as events from '../services/events';
import ParticipationDialog from './ParticipationDialog.vue';

vi.mock('../services/events', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/events')>()),
  fetchEvent: vi.fn(),
  saveParticipation: vi.fn(),
  setParticipation: vi.fn(),
}));

const FORUM = {
  id: 4,
  slug: 'paris-peace-forum-2026',
  title: 'Paris Peace Forum',
  starts_at: '2026-11-10T08:00:00Z',
  ends_at: '2026-11-11T17:00:00Z',
};

async function openDialog(props: Record<string, unknown> = {}) {
  const result = await mountApp(ParticipationDialog, {
    props: { modelValue: false, event: FORUM, participating: false, ...props },
  });
  await result.wrapper.setProps({ modelValue: true });
  await flushPromises();
  return result;
}

const boxes = () =>
  Array.from(document.body.querySelectorAll<HTMLElement>('.participation-dialog .q-checkbox'));
const button = (text: string) =>
  Array.from(document.body.querySelectorAll<HTMLElement>('.participation-dialog button')).find(
    (item) => item.textContent?.includes(text)
  )!;

describe('ParticipationDialog', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.mocked(events.saveParticipation).mockReset().mockResolvedValue();
    vi.mocked(events.setParticipation).mockReset().mockResolvedValue();
    vi.mocked(events.fetchEvent).mockReset();
  });

  it('greys out the confirmation for an account without the member role, and says why', async () => {
    const { wrapper } = await mountApp(ParticipationDialog, {
      props: { modelValue: false, event: FORUM, participating: false },
    });
    useSession().user = {
      id: 1,
      first_name: 'Hélène',
      last_name: 'Marchand',
      slug: 'helene-marchand',
      name: 'Hélène Marchand',
      email: 'helene@example.org',
      roles: ['admin'],
      photo_url: null,
    };
    await wrapper.setProps({ modelValue: true });
    await flushPromises();

    expect(button('Confirmer ma participation').getAttribute('aria-disabled')).toBe('true');
    expect(
      document.body.querySelector('.participation-dialog__members-only')?.textContent
    ).toContain('Il faut être membre du réseau pour participer à un événement.');
    button('Confirmer ma participation').click();
    await flushPromises();
    expect(events.saveParticipation).not.toHaveBeenCalled();
  });

  it('fills the days when it is created already open (first click on the Agenda)', async () => {
    await mountApp(ParticipationDialog, {
      props: { modelValue: true, event: FORUM, participating: false },
    });
    await flushPromises();

    expect(button('Confirmer ma participation').getAttribute('aria-disabled')).not.toBe('true');
    button('Confirmer ma participation').click();
    await flushPromises();

    expect(events.saveParticipation).toHaveBeenCalledWith(4, {
      days: null,
      note: null,
      wants_to_meet: false,
    });
  });

  it('lists the days of the event, all checked, and saves them as all days', async () => {
    const { wrapper } = await openDialog();

    expect(boxes().map((box) => box.textContent?.trim())).toEqual([
      'Mardi 10 novembre',
      'Mercredi 11 novembre',
      'Disponible pour se retrouver sur place',
    ]);
    button('Confirmer ma participation').click();
    await flushPromises();

    expect(events.saveParticipation).toHaveBeenCalledWith(4, {
      days: null,
      note: null,
      wants_to_meet: false,
    });
    expect(wrapper.emitted('saved')).toEqual([[true]]);
    wrapper.unmount();
  });

  it('saves some days, the note and the meeting offer', async () => {
    const { wrapper } = await openDialog({ presetMeet: true });

    boxes()[0]!.click();
    const note = document.body.querySelector<HTMLInputElement>('#participation-note')!;
    note.value = '  J’arrive le matin ';
    note.dispatchEvent(new Event('input'));
    await flushPromises();
    button('Confirmer ma participation').click();
    await flushPromises();

    expect(events.saveParticipation).toHaveBeenCalledWith(4, {
      days: ['2026-11-11'],
      note: 'J’arrive le matin',
      wants_to_meet: true,
    });
    wrapper.unmount();
  });

  it('requires at least one day', async () => {
    const { wrapper } = await openDialog();

    boxes()[0]!.click();
    await flushPromises();
    boxes()[1]!.click();
    await flushPromises();

    expect(document.body.querySelector('[role="alert"]')?.textContent).toContain(
      'Choisissez au moins un jour de présence.'
    );
    expect(button('Confirmer ma participation').hasAttribute('disabled')).toBe(true);
    wrapper.unmount();
  });

  it('loads the current participation to update it, or to withdraw', async () => {
    vi.mocked(events.fetchEvent).mockResolvedValue({
      attendance: { days: ['2026-11-10'], note: 'En soirée' },
      wants_to_meet: true,
    } as events.EventDetail);
    const { wrapper } = await openDialog({ participating: true });

    expect(events.fetchEvent).toHaveBeenCalledWith('paris-peace-forum-2026');
    expect(boxes().map((box) => box.getAttribute('aria-checked'))).toEqual([
      'true',
      'false',
      'true',
    ]);
    expect(document.body.querySelector<HTMLInputElement>('#participation-note')!.value).toBe(
      'En soirée'
    );

    button('Ne plus participer').click();
    await flushPromises();
    expect(events.setParticipation).toHaveBeenCalledWith(4, false);
    expect(wrapper.emitted('saved')).toEqual([[false]]);
    wrapper.unmount();
  });

  it('has no day choice on a one-day event', async () => {
    const { wrapper } = await openDialog({ event: { ...FORUM, ends_at: null } });

    expect(boxes()).toHaveLength(1);
    wrapper.unmount();
  });
});
