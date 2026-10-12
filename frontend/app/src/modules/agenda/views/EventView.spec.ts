// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useSession } from '@/stores/session';
import { mountApp } from '@/testing/mount-app';
import * as events from '../services/events';
import EventView from './EventView.vue';

vi.mock('../services/events', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/events')>()),
  fetchEvent: vi.fn(),
  setParticipation: vi.fn(),
  setMeeting: vi.fn(),
}));

const speaker: events.EventAttendee = {
  id: 2,
  slug: 'aminata-diallo',
  name: 'Aminata Diallo',
  first_name: 'Aminata',
  photo_url: null,
  country: 'Sénégal',
  expertises: ['Médiation communautaire'],
  role: { value: 'speaker', label: 'Intervenante' },
  attendance_note: 'Panel, 10 nov. à 14 h 30',
  wants_to_meet: false,
};

const participant: events.EventAttendee = {
  id: 3,
  slug: 'claire-dubois',
  name: 'Claire Dubois',
  first_name: 'Claire',
  photo_url: null,
  country: 'France',
  expertises: [],
  role: { value: 'participant', label: 'Participante' },
  attendance_note: null,
  wants_to_meet: true,
};

const FORUM: events.EventDetail = {
  id: 4,
  slug: 'paris-peace-forum-2026',
  title: 'Paris Peace Forum',
  type: { value: 'international_forum', label: 'Forum international' },
  starts_at: '2026-11-10T08:00:00Z',
  ends_at: '2026-11-11T17:00:00Z',
  place: 'Paris, France',
  city: 'Paris',
  venue: 'Palais de Chaillot',
  is_online: false,
  is_past: false,
  status: 'upcoming',
  wants_to_meet: false,
  description: 'Premier paragraphe.\n\nSecond paragraphe.',
  photo_url: null,
  organizer: { name: 'Forum de Paris sur la Paix', detail: 'Association loi 1901, Paris' },
  languages: null,
  links: [{ label: 'Site officiel', url: 'https://parispeaceforum.org/fr/' }],
  themes: ['Multilatéralisme'],
  attendee_count: 2,
  attendees: [speaker, participant],
  is_participating: false,
  program: [
    {
      date: '2026-11-10',
      sessions: [
        {
          id: 1,
          time: '14:30',
          title: 'Femmes médiatrices',
          detail: 'Panel',
          is_network: true,
          speaker: { name: 'Aminata Diallo', slug: 'aminata-diallo', photo_url: null },
        },
      ],
    },
  ],
};

async function openEvent(path = '/agenda/paris-peace-forum-2026', viewerId = 1) {
  const result = await mountApp(EventView, {
    path,
    global: { stubs: { QPage: { template: '<div><slot /></div>' } } },
  });
  const session = useSession();
  session.user = { id: viewerId } as typeof session.user;
  await flushPromises();
  return result;
}

describe('EventView', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.mocked(events.fetchEvent).mockReset().mockResolvedValue(structuredClone(FORUM));
    vi.mocked(events.setParticipation).mockReset().mockResolvedValue();
    vi.mocked(events.setMeeting).mockReset().mockResolvedValue();
  });

  it('shows the header, the description, the themes and the practical details', async () => {
    const { wrapper } = await openEvent();

    expect(events.fetchEvent).toHaveBeenCalledWith('paris-peace-forum-2026', expect.anything());
    expect(wrapper.find('h1').text()).toBe('Paris Peace Forum');
    expect(wrapper.text()).toContain('Forum international');
    expect(wrapper.text()).toContain('À venir');
    expect(wrapper.text()).toContain('10–11 novembre 2026');
    expect(wrapper.text()).toContain('Palais de Chaillot, Paris, France');
    expect(wrapper.findAll('.event-card__section p').map((p) => p.text())).toEqual([
      'Premier paragraphe.',
      'Second paragraphe.',
    ]);
    expect(wrapper.find('.event-chip').text()).toBe('Multilatéralisme');
    expect(wrapper.find('.event-side').text()).toContain('Forum de Paris sur la Paix');
    expect(wrapper.find('.event-side a').attributes('href')).toBe(
      'https://parispeaceforum.org/fr/'
    );
    // Langues non renseignées : ligne omise
    expect(wrapper.find('.event-side__facts').text()).not.toContain('Langues');
    wrapper.unmount();
  });

  it('shows the network block, speakers first, with the number of speakers', async () => {
    const { wrapper } = await openEvent();

    const block = wrapper.find('.event-network');
    expect(block.find('h2').text()).toBe('2 médiatrices présentes');
    expect(block.findAll('.attendee-card h3').map((name) => name.text())).toEqual([
      'Aminata Diallo',
      'Claire Dubois',
    ]);
    expect(block.find('.event-network__foot p').text()).toBe(
      '1 intervenante du réseau prend la parole le 10 novembre.'
    );
    expect(block.find('.event-network__meet').text()).toContain(
      'Proposer de se retrouver sur place'
    );
    // Claire s'est dite disponible pour se retrouver sur place
    expect(block.findAll('.attendee-card__meet')).toHaveLength(1);
    wrapper.unmount();
  });

  it('opens the mediators tab from the url and filters them by role', async () => {
    const { wrapper } = await openEvent('/agenda/paris-peace-forum-2026?onglet=mediatrices');

    expect(wrapper.find('.event-people__toolbar h2').text()).toBe(
      '2 médiatrices du réseau présentes'
    );
    expect(wrapper.findAll('.event-people__grid li')).toHaveLength(2);

    const speakersOnly = wrapper
      .findAll('.event-people__filter .q-btn')
      .find((button) => button.text() === 'Intervenantes')!;
    await speakersOnly.trigger('click');
    expect(wrapper.findAll('.event-people__grid li')).toHaveLength(1);
    expect(wrapper.find('.event-people__grid').text()).toContain('Médiation communautaire');
    wrapper.unmount();
  });

  it('shows the program, with the network sessions and their speaker', async () => {
    const { wrapper } = await openEvent('/agenda/paris-peace-forum-2026?onglet=programme');

    expect(wrapper.find('.event-program h3').text()).toBe('Mardi 10 novembre');
    expect(wrapper.find('.event-program__slot--network').text()).toContain(
      'Aminata Diallo intervient pour le RIMeF'
    );
    wrapper.unmount();
  });

  it('opens the message window on the page, and hides the envelope on her own card', async () => {
    const { wrapper } = await openEvent('/agenda/paris-peace-forum-2026?onglet=mediatrices', 3);

    const writes = wrapper.findAll('.event-people__grid .attendee-card__write');
    expect(writes).toHaveLength(1);
    await writes[0]!.trigger('click');
    await flushPromises();

    expect(document.body.querySelector('#contact-title')?.textContent).toBe('Contacter Aminata');
    expect(wrapper.vm.$route.fullPath).toBe('/agenda/paris-peace-forum-2026?onglet=mediatrices');
    wrapper.unmount();
  });

  it('offers to meet on site, then shows the availability', async () => {
    const { wrapper } = await openEvent();

    vi.mocked(events.fetchEvent).mockResolvedValue({
      ...structuredClone(FORUM),
      is_participating: true,
      wants_to_meet: true,
    });
    await wrapper.find('.event-network__meet').trigger('click');
    await flushPromises();

    expect(events.setMeeting).toHaveBeenCalledWith(4, true);
    const meet = wrapper.find('.event-network__meet');
    expect(meet.text()).toContain('Disponible pour se retrouver');
    expect(meet.attributes('aria-pressed')).toBe('true');
    wrapper.unmount();
  });

  it('records the participation and reloads the event', async () => {
    const { wrapper } = await openEvent();

    vi.mocked(events.fetchEvent).mockResolvedValue({
      ...structuredClone(FORUM),
      is_participating: true,
      attendee_count: 3,
    });
    await wrapper.find('.event-actions__join').trigger('click');
    await flushPromises();

    expect(events.setParticipation).toHaveBeenCalledWith(4, true);
    expect(wrapper.find('.event-actions__join').text()).toContain('Je participe');
    wrapper.unmount();
  });

  it('offers neither participation nor calendar on a past event', async () => {
    vi.mocked(events.fetchEvent).mockResolvedValue({
      ...structuredClone(FORUM),
      status: 'past',
      is_past: true,
    });
    const { wrapper } = await openEvent();

    expect(wrapper.text()).toContain('Terminé');
    expect(wrapper.find('.event-actions__join').exists()).toBe(false);
    expect(wrapper.find('[aria-label="Plus d’actions"]').exists()).toBe(true);
    wrapper.unmount();
  });

  it('says when the event does not exist', async () => {
    vi.mocked(events.fetchEvent).mockRejectedValue(
      Object.assign(new Error('Not found'), { isAxiosError: true, response: { status: 404 } })
    );
    const { wrapper } = await openEvent();

    expect(wrapper.text()).toContain('Événement introuvable');
    wrapper.unmount();
  });
});
