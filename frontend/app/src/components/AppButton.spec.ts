// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import AppButton from './AppButton.vue';

describe('AppButton', () => {
  it('is a primary button by default, without capitals', async () => {
    const { wrapper } = await mountApp(AppButton, { props: { label: 'Se connecter' } });

    const button = wrapper.find('.q-btn');
    expect(button.classes()).toContain('app-btn--primary');
    expect(button.classes()).toContain('app-btn--md');
    expect(button.classes()).toContain('q-btn--no-uppercase');
    expect(button.text()).toBe('Se connecter');
    wrapper.unmount();
  });

  it('passes the variant, the size and the props of q-btn through', async () => {
    const { wrapper } = await mountApp(AppButton, {
      props: { variant: 'outline', size: 'sm', label: 'Réseau', to: { name: 'network' } },
    });

    const link = wrapper.find('a.q-btn');
    expect(link.attributes('href')).toBe('/reseau');
    expect(link.classes()).toEqual(expect.arrayContaining(['app-btn--outline', 'app-btn--sm']));
    wrapper.unmount();
  });

  it('shows a spinner instead of the label while loading', async () => {
    const { wrapper } = await mountApp(AppButton, { props: { label: 'Envoyer', loading: true } });

    expect(wrapper.find('.q-spinner').exists()).toBe(true);
    wrapper.unmount();
  });
});
