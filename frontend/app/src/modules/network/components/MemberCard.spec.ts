// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { Member } from '../services/members';
import MemberCard from './MemberCard.vue';

const AMINATA: Member = {
  id: 7,
  slug: 'aminata-diallo',
  name: 'Aminata Diallo',
  country: { code: 'SN', name: 'Sénégal' },
  region: 'Afrique de l’Ouest',
  organization: 'Société civile',
  is_available: true,
  photo_url: null,
  is_favorite: false,
  next_event: null,
  expertises: ['Médiation communautaire', 'Femmes, paix et sécurité', 'Troisième expertise'],
};

describe('MemberCard', () => {
  it('shows the name, country, two expertises and availability, linked to the profile', async () => {
    const { wrapper } = await mountApp(MemberCard, { props: { member: AMINATA } });

    expect(wrapper.find('h3 a').text()).toBe('Aminata Diallo');
    expect(wrapper.find('h3 a').attributes('href')).toBe('/reseau/aminata-diallo');
    expect(wrapper.text()).toContain('Sénégal');
    expect(wrapper.findAll('.member-card__expertises li').map((li) => li.text())).toEqual([
      'Médiation communautaire',
      'Femmes, paix et sécurité',
    ]);
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
        },
      },
    });

    expect(wrapper.find('h3').text()).toBe('Mireille Kouassi');
    expect(wrapper.find('.member-card__country').exists()).toBe(false);
    expect(wrapper.find('.member-card__expertises').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Disponible');
    wrapper.unmount();
  });

  it('shows the photo of the mediator in the portrait when she has one', async () => {
    const { wrapper } = await mountApp(MemberCard, {
      props: { member: { ...AMINATA, photo_url: '/api/members/7/photo?v=1' } },
    });

    expect(wrapper.find('.member-card__photo').attributes('src')).toBe('/api/members/7/photo?v=1');
    expect(wrapper.find('.member-avatar').exists()).toBe(false);
    wrapper.unmount();
  });

  it('labels the bookmark and asks to toggle the favorite', async () => {
    const { wrapper } = await mountApp(MemberCard, { props: { member: AMINATA } });

    const bookmark = wrapper.find('.member-card__bookmark');
    expect(bookmark.attributes('aria-label')).toBe('Ajouter Aminata Diallo aux favoris');
    expect(bookmark.attributes('aria-pressed')).toBe('false');
    await bookmark.trigger('click');
    expect(wrapper.emitted('toggleFavorite')).toHaveLength(1);
    wrapper.unmount();
  });

  it('shows a saved favorite as pressed', async () => {
    const { wrapper } = await mountApp(MemberCard, {
      props: { member: { ...AMINATA, is_favorite: true } },
    });

    const bookmark = wrapper.find('.member-card__bookmark');
    expect(bookmark.attributes('aria-pressed')).toBe('true');
    expect(bookmark.attributes('aria-label')).toBe('Retirer Aminata Diallo des favoris');
    wrapper.unmount();
  });

  it('shows the next event instead of the availability', async () => {
    const { wrapper } = await mountApp(MemberCard, {
      props: {
        member: {
          ...AMINATA,
          next_event: { id: 1, title: 'Paris Peace Forum', starts_at: '2026-11-12T08:00:00+00:00' },
        },
      },
    });

    expect(wrapper.find('.member-card__next').text()).toMatch(
      /Prochainement :\s*Paris Peace Forum/
    );
    expect(wrapper.find('.member-card__next a').attributes('href')).toBe('/agenda');
    expect(wrapper.text()).not.toContain('Disponible pour collaboration');
    wrapper.unmount();
  });
});
