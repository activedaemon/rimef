// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import EditorialBand from './EditorialBand.vue';

describe('EditorialBand', () => {
  it('invites the member to complete her profile', async () => {
    const { wrapper } = await mountApp(EditorialBand);

    const button = wrapper.find('a');
    expect(button.text()).toBe('Compléter mon profil');
    expect(button.attributes('href')).toBe('/profil/modifier');
    wrapper.unmount();
  });
});
