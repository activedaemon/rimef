// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';
import { QLayout } from 'quasar';

import { mountApp } from '@/testing/mount-app';
import AppTabBar from './AppTabBar.vue';

// q-footer doit être placé dans un q-layout
const InLayout = defineComponent({ render: () => h(QLayout, null, () => h(AppTabBar)) });

describe('AppTabBar', () => {
  it('shows the five tabs with the current one marked', async () => {
    const { wrapper } = await mountApp(InLayout, { path: '/profil/modifier' });

    const tabs = wrapper.findAll('nav[aria-label="Navigation mobile"] a');
    expect(tabs.map((tab) => tab.text())).toEqual([
      'Accueil',
      'Réseau',
      'Agenda',
      'Ressources',
      'Profil',
    ]);
    expect(wrapper.find('[aria-current="page"]').text()).toBe('Profil');

    wrapper.unmount();
  });
});
