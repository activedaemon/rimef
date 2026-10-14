// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { LibraryResource } from '../services/resources';
import ResourceCard from './ResourceCard.vue';

const REPORT: LibraryResource = {
  id: 1,
  slug: 'women-mediators',
  title: 'Women Mediators and Inclusive Peace Processes',
  type: { value: 'report', label: 'Rapport', action: 'Lire' },
  source: 'Conciliation Resources',
  author: null,
  published_on: '2026-04-14',
  duration_minutes: null,
  url: 'https://www.c-r.org/',
  themes: [
    { id: 4, name: 'Femmes, paix et sécurité' },
    { id: 5, name: 'Médiation internationale' },
    { id: 6, name: 'Troisième thématique' },
  ],
  is_favorite: false,
};

describe('ResourceCard', () => {
  it('shows the type, title, source, month and two themes, linked to the resource page', async () => {
    const { wrapper } = await mountApp(ResourceCard, { props: { resource: REPORT } });

    expect(wrapper.find('.resource-type').text()).toBe('Rapport');
    const link = wrapper.find('h3 a');
    expect(link.text()).toBe('Women Mediators and Inclusive Peace Processes');
    expect(link.attributes('href')).toBe('/ressources/women-mediators');
    expect(wrapper.find('.resource-card__source').text()).toBe('Conciliation Resources');
    expect(wrapper.find('.resource-card__date').text()).toBe('Avril 2026');
    expect(wrapper.findAll('.resource-card__themes li').map((li) => li.text())).toEqual([
      'Femmes, paix et sécurité',
      'Médiation internationale',
    ]);
    expect(wrapper.find('.resource-card__cta').text()).toBe('Lire');
    wrapper.unmount();
  });

  it('shows the mediator who wrote it, the duration and the listening call', async () => {
    const { wrapper } = await mountApp(ResourceCard, {
      props: {
        resource: {
          ...REPORT,
          type: { value: 'podcast', label: 'Podcast', action: 'Écouter' },
          source: null,
          author: { id: 7, slug: 'aminata-diallo', name: 'Aminata Diallo', photo_url: null },
          duration_minutes: 34,
        },
      },
    });

    expect(wrapper.find('.resource-card__source').text()).toMatch(
      /Aminata Diallo\s*· Médiatrice RIMeF/
    );
    expect(wrapper.find('.resource-card__date').text()).toBe('Avril 2026 · 34 min');
    expect(wrapper.find('.resource-card__cta').classes()).toContain('resource-card__cta--listen');
    wrapper.unmount();
  });

  it('toggles the bookmark', async () => {
    const { wrapper } = await mountApp(ResourceCard, {
      props: { resource: { ...REPORT, is_favorite: true } },
    });

    const save = wrapper.find('.resource-card__save');
    expect(save.attributes('aria-pressed')).toBe('true');
    expect(save.attributes('aria-label')).toBe('Retirer des favoris');
    await save.trigger('click');
    expect(wrapper.emitted('toggleFavorite')).toHaveLength(1);
    wrapper.unmount();
  });
});
