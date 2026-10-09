// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { Member } from '../services/members';
import MemberCard from './MemberCard.vue';

const AMINATA: Member = {
  id: 7,
  name: 'Aminata Diallo',
  country: { code: 'SN', name: 'Sénégal' },
  region: 'Afrique de l’Ouest',
  organization: 'Société civile',
  is_available: true,
  expertises: ['Médiation communautaire', 'Femmes, paix et sécurité', 'Troisième expertise'],
  languages: [
    { code: 'fr', name: 'Français' },
    { code: 'wo', name: 'Wolof' },
  ],
};

describe('MemberCard', () => {
  it('shows the name, country, two expertises, languages and availability, linked to the profile', async () => {
    const { wrapper } = await mountApp(MemberCard, { props: { member: AMINATA } });

    expect(wrapper.find('h3 a').text()).toBe('Aminata Diallo');
    expect(wrapper.find('h3 a').attributes('href')).toBe('/reseau/7');
    expect(wrapper.text()).toContain('Sénégal');
    expect(wrapper.findAll('.member-card__expertises li').map((li) => li.text())).toEqual([
      'Médiation communautaire',
      'Femmes, paix et sécurité',
    ]);
    expect(wrapper.find('.member-card__languages').text()).toMatch(/FR\s*·\s*WO/);
    expect(wrapper.find('.member-card__languages').attributes('aria-label')).toBe(
      'Langues : Français, Wolof'
    );
    expect(wrapper.text()).toContain('Disponible pour collaboration');
    wrapper.unmount();
  });

  it('shows only the name when the profile is empty', async () => {
    const { wrapper } = await mountApp(MemberCard, {
      props: {
        member: {
          ...AMINATA,
          name: 'Mireille Kouassi',
          country: null,
          is_available: false,
          expertises: [],
          languages: [],
        },
      },
    });

    expect(wrapper.find('h3').text()).toBe('Mireille Kouassi');
    expect(wrapper.find('.member-card__country').exists()).toBe(false);
    expect(wrapper.find('.member-card__expertises').exists()).toBe(false);
    expect(wrapper.find('.member-card__languages').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Disponible');
    wrapper.unmount();
  });
});
