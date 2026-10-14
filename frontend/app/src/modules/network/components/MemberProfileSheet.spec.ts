// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import * as messages from '@modules/messages/services/messages';
import { mountApp } from '@/testing/mount-app';
import type { MemberProfile } from '../services/members';
import MemberProfileSheet from './MemberProfileSheet.vue';

vi.mock('@modules/messages/services/messages', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@modules/messages/services/messages')>()),
  fetchMessages: vi.fn().mockResolvedValue({
    messages: [
      {
        id: 1,
        body: 'Bonjour Aminata',
        sent_at: '2026-10-10T12:00:00Z',
        is_mine: true,
      },
    ],
    hasMore: false,
  }),
  markConversationRead: vi.fn().mockResolvedValue(undefined),
}));
vi.mock('@modules/notifications/services/notifications', () => ({
  fetchUnreadCounts: vi.fn().mockResolvedValue({ notifications: 0, messages: 0 }),
}));

const AMINATA: MemberProfile = {
  id: 7,
  slug: 'aminata-diallo',
  name: 'Aminata Diallo',
  first_name: 'Aminata',
  photo_url: null,
  country: { code: 'SN', name: 'Sénégal' },
  city: 'Dakar',
  organization: 'Société civile',
  job_title: 'Conseillère en paix et sécurité',
  tagline: 'Favoriser le dialogue inclusif.',
  bio: 'Médiatrice en Afrique de l’Ouest.',
  years_of_experience: 15,
  audiences: 'Femmes, jeunes',
  is_available: false,
  is_favorite: false,
  links: [],
  conversation: null,
  expertises: ['Médiation communautaire', 'Gouvernance locale'],
  zones: [
    { code: 'SN', name: 'Sénégal' },
    { code: 'ML', name: 'Mali' },
  ],
  upcoming_events: [],
  resources: [],
};

const EMPTY: MemberProfile = {
  ...AMINATA,
  name: 'Mireille Kouassi',
  first_name: 'Mireille',
  country: null,
  city: null,
  organization: null,
  job_title: null,
  tagline: null,
  bio: null,
  years_of_experience: null,
  audiences: null,
  expertises: [],
  zones: [],
};

describe('MemberProfileSheet', () => {
  it('shows the header, the key figures and the profile sections', async () => {
    const { wrapper } = await mountApp(MemberProfileSheet, { props: { profile: AMINATA } });

    expect(wrapper.find('h1').text()).toBe('Aminata Diallo');
    expect(wrapper.find('.profile-header__role').text()).toBe('Conseillère en paix et sécurité');
    expect(wrapper.find('.profile-header__facts').text()).toContain('Dakar, Sénégal');
    expect(wrapper.find('.profile-header__facts').text()).toContain('Société civile');
    expect(wrapper.find('blockquote').text()).toBe('« Favoriser le dialogue inclusif. »');

    const marks = wrapper
      .findAll('.profile-marks li')
      .map((li) => [li.find('b, p').text(), li.find('span').text()]);
    expect(marks).toEqual([
      ['15 ans', 'd’expérience en médiation'],
      ['2', 'zones d’intervention'],
      ['Femmes, jeunes', 'publics accompagnés'],
    ]);

    expect(wrapper.find('#profile-about').exists()).toBe(true);
    expect(wrapper.findAll('#profile-zones + ul li').map((li) => li.text())).toEqual([
      'Sénégal',
      'Mali',
    ]);
    expect(wrapper.text()).toContain('Aucun événement à venir pour le moment.');
    expect(wrapper.text()).toContain('Aucune contribution pour le moment.');
    wrapper.unmount();
  });

  it('keeps only the identity of an empty profile', async () => {
    const { wrapper } = await mountApp(MemberProfileSheet, { props: { profile: EMPTY } });

    expect(wrapper.find('.profile-header__role').text()).toBe('Médiatrice');
    expect(wrapper.find('.profile-header__facts').exists()).toBe(false);
    expect(wrapper.find('blockquote').exists()).toBe(false);
    expect(wrapper.find('.profile-marks').exists()).toBe(false);
    expect(wrapper.find('.profile-section').exists()).toBe(false);
    expect(wrapper.text()).toContain('Mireille n’a pas encore complété son profil.');
    wrapper.unmount();
  });

  it('shows only the Profile tab without events, resources or conversation', async () => {
    const { wrapper } = await mountApp(MemberProfileSheet, { props: { profile: AMINATA } });

    const tabs = wrapper.findAll('.q-tab');
    expect(tabs.map((tab) => tab.text())).toEqual(['Profil']);
    expect(wrapper.find('.side-card__empty').text()).toBe(
      'Aucun événement à venir pour le moment.'
    );
    wrapper.unmount();
  });

  it('lists her next events in the side card and in the Events tab, with her role', async () => {
    const profile: MemberProfile = {
      ...AMINATA,
      upcoming_events: [
        {
          id: 4,
          slug: 'paris-peace-forum-2026',
          title: 'Paris Peace Forum',
          summary: 'Le rendez-vous international de la diplomatie.',
          type: { value: 'international_forum', label: 'Forum international' },
          starts_at: '2026-11-10T08:00:00Z',
          ends_at: '2026-11-11T17:00:00Z',
          place: 'Paris, France',
          is_online: false,
          role: { value: 'speaker', label: 'Intervenante' },
        },
        {
          id: 5,
          slug: 'sommet-de-la-francophonie-2026',
          title: 'Sommet de la Francophonie',
          summary: null,
          type: { value: 'summit', label: 'Sommet' },
          starts_at: '2026-11-18T08:00:00Z',
          ends_at: null,
          place: 'Kigali, Rwanda',
          is_online: false,
          role: { value: 'participant', label: 'Participante' },
        },
      ],
    };
    const { wrapper } = await mountApp(MemberProfileSheet, { props: { profile } });

    const side = wrapper.findAll('.profile-side .member-event');
    expect(side).toHaveLength(2);
    expect(side[0]!.find('h3 a').attributes('href')).toBe('/agenda/paris-peace-forum-2026');
    expect(side[0]!.find('.member-event__role--speaker').text()).toBe('Intervenante');
    expect(side[1]!.text()).toContain('Participante');

    const tabs = wrapper.findAll('.q-tab');
    expect(tabs[1]!.text()).toBe('Événements2');
    await tabs[1]!.trigger('click');
    expect(wrapper.find('.profile-list__head p').text()).toBe(
      '2 rendez-vous où Aminata Diallo sera présente'
    );
    expect(wrapper.find('.member-event--wide .member-event__eyebrow').text()).toBe(
      'Forum international · 2026'
    );
    expect(wrapper.find('.member-event--wide .member-event__summary').text()).toBe(
      'Le rendez-vous international de la diplomatie.'
    );
    // Pas de résumé dans la carte de la colonne
    expect(wrapper.find('.profile-side .member-event__summary').exists()).toBe(false);
    wrapper.unmount();
  });

  it('lists her contributions in the side card and in the Resources tab', async () => {
    const report = {
      id: 1,
      slug: 'women-mediators',
      title: 'Women Mediators',
      type: { value: 'report', label: 'Rapport', action: 'Lire' },
      source: 'Conciliation Resources',
      published_on: '2026-04-14',
      duration_minutes: null,
      role: { value: 'contributor' as const, label: 'Contributrice' },
      event: { slug: 'paris-peace-forum-2026', title: 'Paris Peace Forum' },
    };
    const profile: MemberProfile = {
      ...AMINATA,
      resources: [
        report,
        {
          ...report,
          id: 2,
          slug: 'paroles',
          title: 'Paroles',
          published_on: '2025-12-09',
          event: null,
        },
        {
          ...report,
          id: 3,
          slug: 'guide',
          title: 'Guide',
          published_on: '2025-10-06',
          event: null,
        },
      ],
    };
    const { wrapper } = await mountApp(MemberProfileSheet, { props: { profile } });

    const side = wrapper.findAll('.profile-side .resource-row');
    expect(side).toHaveLength(2);
    expect(side[0]!.find('h3 a').attributes('href')).toBe('/ressources/women-mediators');
    expect(side[0]!.find('.resource-row__facts').text()).toBe('Rapport · Avril 2026');

    const tabs = wrapper.findAll('.q-tab');
    // Sans événement à venir : Profil, puis Ressources
    expect(tabs.map((item) => item.text())).toEqual(['Profil', 'Ressources3']);
    await tabs[1]!.trigger('click');
    const rows = wrapper.findAll('.profile-resources.profile-list__rows .resource-row');
    expect(rows).toHaveLength(3);
    expect(rows[0]!.find('.eyebrow').text()).toBe('Rapport · Avril 2026 · Contributrice');
    expect(rows[0]!.find('.resource-row__note a').attributes('href')).toBe(
      '/agenda/paris-peace-forum-2026'
    );
    expect(rows[0]!.find('.resource-row__action').text()).toBe('Lire');
    expect(rows[1]!.find('.resource-row__note').exists()).toBe(false);
    wrapper.unmount();
  });

  it('adds the Messages tab only when a conversation exists, with its unread messages', async () => {
    const withConversation = { ...AMINATA, conversation: { id: 3, unread_count: 2 } };
    const { wrapper, router } = await mountApp(MemberProfileSheet, {
      path: '/reseau/aminata-diallo',
      props: { profile: withConversation },
    });

    const tabs = wrapper.findAll('.q-tab');
    expect(tabs).toHaveLength(2);
    expect(tabs[1]!.attributes('aria-label')).toBe('Messages, 2 non lus');
    expect(tabs[1]!.find('.profile-tabs__badge').text()).toBe('2');

    await tabs[1]!.trigger('click');
    await flushPromises();

    expect(router.currentRoute.value.query.onglet).toBe('messages');
    expect(messages.fetchMessages).toHaveBeenCalledWith(3);
    expect(wrapper.find('.message__body').text()).toBe('Bonjour Aminata');
    expect(messages.markConversationRead).toHaveBeenCalledWith(3);
    expect(wrapper.find('.profile-tabs__badge').exists()).toBe(false);
    wrapper.unmount();
  });

  it('opens the tab given in the URL, but not a hidden one', async () => {
    for (const onglet of ['messages', 'evenements', 'ressources']) {
      const { wrapper } = await mountApp(MemberProfileSheet, {
        path: `/reseau/aminata-diallo?onglet=${onglet}`,
        props: { profile: AMINATA },
      });

      expect(wrapper.find('.q-tab--active').text()).toBe('Profil');
      wrapper.unmount();
    }
  });

  it('shows her links, opened in a new tab', async () => {
    const { wrapper } = await mountApp(MemberProfileSheet, {
      props: {
        profile: {
          ...AMINATA,
          links: [
            {
              type: 'linkedin',
              label: 'LinkedIn',
              url: 'https://www.linkedin.com/in/aminata-diallo/',
            },
            { type: 'other', label: 'Podcast', url: 'https://podcast.example.org' },
          ],
        },
      },
    });

    const links = wrapper.findAll('.profile-links a');
    expect(links.map((link) => link.text())).toEqual([
      'linkedin.com/in/aminata-diallo (nouvel onglet)',
      'podcast.example.org (nouvel onglet)',
    ]);
    expect(links[0]!.attributes('target')).toBe('_blank');
    expect(links[0]!.attributes('rel')).toContain('noopener');
    wrapper.unmount();
  });
});
