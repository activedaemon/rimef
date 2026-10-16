// @vitest-environment happy-dom
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { QLayout } from 'quasar';

import { mountApp } from '@/testing/mount-app';
import AppTabBar from './AppTabBar.vue';

// q-footer doit être placé dans un q-layout
const InLayout = defineComponent({ render: () => h(QLayout, null, () => h(AppTabBar)) });

describe('AppTabBar', () => {
  it('shows the four tabs with the current one selected', async () => {
    const { wrapper } = await mountApp(InLayout, { path: '/reseau' });

    const tabs = wrapper.findAll('nav[aria-label="Navigation mobile"] .q-tab .q-tab__label');
    expect(tabs.map((tab) => tab.text())).toEqual([
      'Accueil',
      'Réseau',
      'Agenda',
      'Ressources',
    ]);
    // q-route-tab : l'onglet courant porte aria-selected,
    // calculé par q-tabs juste après le rendu
    await vi.waitFor(() =>
      expect(wrapper.find('.q-tab[aria-selected="true"] .q-tab__label').text()).toBe('Réseau')
    );

    wrapper.unmount();
  });
});
