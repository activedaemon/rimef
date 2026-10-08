// @vitest-environment happy-dom
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { QLayout } from 'quasar';

import { mountApp } from '@/testing/mount-app';
import AppTabBar from './AppTabBar.vue';

// q-footer doit être placé dans un q-layout
const InLayout = defineComponent({ render: () => h(QLayout, null, () => h(AppTabBar)) });

describe('AppTabBar', () => {
  it('shows the five tabs with the current one selected', async () => {
    const { wrapper } = await mountApp(InLayout, { path: '/profil/modifier' });

    const tabs = wrapper.findAll('nav[aria-label="Navigation mobile"] .q-tab .q-tab__label');
    expect(tabs.map((tab) => tab.text())).toEqual([
      'Accueil',
      'Réseau',
      'Agenda',
      'Ressources',
      'Profil',
    ]);
    // q-route-tab : l'onglet courant porte aria-selected (Profil reste actif en édition),
    // calculé par q-tabs juste après le rendu
    await vi.waitFor(() =>
      expect(wrapper.find('.q-tab[aria-selected="true"] .q-tab__label').text()).toBe('Profil')
    );

    wrapper.unmount();
  });
});
