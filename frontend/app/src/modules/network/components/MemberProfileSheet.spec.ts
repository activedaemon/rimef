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
  region: 'Afrique de l’Ouest',
  organization: 'Société civile',
  job_title: 'Conseillère en paix et sécurité',
  tagline: 'Favoriser le dialogue inclusif.',
  bio: 'Médiatrice en Afrique de l’Ouest.',
  years_of_experience: 15,
  audiences: 'Femmes, jeunes',
  is_available: false,
  is_favorite: false,
  conversation: null,
  expertises: ['Médiation communautaire', 'Gouvernance locale'],
  zones: [
    { code: 'SN', name: 'Sénégal' },
    { code: 'ML', name: 'Mali' },
  ],
};

const EMPTY: MemberProfile = {
  ...AMINATA,
  name: 'Mireille Kouassi',
  first_name: 'Mireille',
  country: null,
  city: null,
  region: null,
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
    expect(wrapper.find('.profile-header__role').text()).toContain(
      'Conseillère en paix et sécurité'
    );
    expect(wrapper.find('.profile-header__facts').text()).toContain('Dakar, Sénégal');
    expect(wrapper.find('.profile-header__facts').text()).toContain('Société civile');
    expect(wrapper.find('blockquote').text()).toBe('« Favoriser le dialogue inclusif. »');

    const marks = wrapper
      .findAll('.profile-marks li')
      .map((li) => [li.find('b, p').text(), li.find('span').text()]);
    expect(marks).toEqual([
      ['15 ans', 'd’expérience en médiation'],
      ['2', 'pays d’intervention'],
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

    expect(wrapper.find('.profile-header__facts').exists()).toBe(false);
    expect(wrapper.find('blockquote').exists()).toBe(false);
    expect(wrapper.find('.profile-marks').exists()).toBe(false);
    expect(wrapper.find('.profile-section').exists()).toBe(false);
    expect(wrapper.text()).toContain('Mireille n’a pas encore complété son profil.');
    wrapper.unmount();
  });

  it('shows the three tabs, events and resources waiting for their content', async () => {
    const { wrapper } = await mountApp(MemberProfileSheet, { props: { profile: AMINATA } });

    const tabs = wrapper.findAll('.q-tab');
    expect(tabs.map((tab) => tab.text())).toEqual(['Profil', 'Événements', 'Ressources']);

    await tabs[1]!.trigger('click');
    expect(wrapper.text()).toContain('Les événements arrivent bientôt');
    wrapper.unmount();
  });

  it('adds the Messages tab only when a conversation exists, with its unread messages', async () => {
    const withConversation = { ...AMINATA, conversation: { id: 3, unread_count: 2 } };
    const { wrapper, router } = await mountApp(MemberProfileSheet, {
      path: '/reseau/aminata-diallo',
      props: { profile: withConversation },
    });

    const tabs = wrapper.findAll('.q-tab');
    expect(tabs).toHaveLength(4);
    expect(tabs[3]!.attributes('aria-label')).toBe('Messages, 2 non lus');
    expect(tabs[3]!.find('.profile-tabs__badge').text()).toBe('2');

    await tabs[3]!.trigger('click');
    await flushPromises();

    expect(router.currentRoute.value.query.onglet).toBe('messages');
    expect(messages.fetchMessages).toHaveBeenCalledWith(3);
    expect(wrapper.find('.message__body').text()).toBe('Bonjour Aminata');
    expect(messages.markConversationRead).toHaveBeenCalledWith(3);
    expect(wrapper.find('.profile-tabs__badge').exists()).toBe(false);
    wrapper.unmount();
  });

  it('opens the tab given in the URL, but not Messages without a conversation', async () => {
    const { wrapper } = await mountApp(MemberProfileSheet, {
      path: '/reseau/aminata-diallo?onglet=messages',
      props: { profile: AMINATA },
    });

    expect(wrapper.find('.q-tab--active').text()).toBe('Profil');
    wrapper.unmount();
  });
});
