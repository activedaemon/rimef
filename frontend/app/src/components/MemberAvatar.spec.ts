// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import MemberAvatar from './MemberAvatar.vue';

describe('MemberAvatar', () => {
  it('shows the initials of the first two names without a photo', async () => {
    const { wrapper } = await mountApp(MemberAvatar, { props: { name: 'Marie-Joëlle Zahar' } });

    expect(wrapper.text()).toBe('MZ');
    expect(wrapper.find('img').exists()).toBe(false);
    wrapper.unmount();
  });

  it('shows the photo, lazily loaded, when there is one', async () => {
    const { wrapper } = await mountApp(MemberAvatar, {
      props: { name: 'Aminata Diallo', photo: '/api/members/1/photo?v=1', size: 72 },
    });

    const img = wrapper.find('img');
    expect(img.attributes('src')).toBe('/api/members/1/photo?v=1');
    expect(img.attributes('loading')).toBe('lazy');
    expect(img.attributes('alt')).toBe('');
    wrapper.unmount();
  });

  it('falls back to the initials when the photo cannot be loaded', async () => {
    const { wrapper } = await mountApp(MemberAvatar, {
      props: { name: 'Aminata Diallo', photo: '/introuvable.webp' },
    });

    await wrapper.find('img').trigger('error');
    expect(wrapper.find('img').exists()).toBe(false);
    expect(wrapper.text()).toBe('AD');
    wrapper.unmount();
  });
});
