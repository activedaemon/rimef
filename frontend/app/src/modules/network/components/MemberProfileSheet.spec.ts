// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { MemberProfile } from '../services/members';
import MemberProfileSheet from './MemberProfileSheet.vue';

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
});
