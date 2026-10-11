// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import * as auth from '@modules/auth/services/auth';
import { useInbox } from '@/stores/inbox';
import { useSession } from '@/stores/session';
import { mountApp } from '@/testing/mount-app';
import AccountMenu from './AccountMenu.vue';

vi.mock('@modules/auth/services/auth', () => ({
  fetchCurrentUser: vi.fn(),
  login: vi.fn(),
  logout: vi.fn(),
}));

async function openMenu(overrides: Partial<auth.AuthenticatedUser> = {}) {
  const result = await mountApp(AccountMenu);
  useSession().user = {
    id: 1,
    first_name: 'Aminata',
    last_name: 'Diallo',
    slug: 'aminata-diallo',
    name: 'Aminata Diallo',
    email: 'aminata@example.org',
    roles: ['admin'],
    photo_url: null,
    ...overrides,
  };
  await flushPromises();
  await result.wrapper.find('button').trigger('click');
  await flushPromises();
  return result;
}

describe('AccountMenu', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    document.body.innerHTML = '';
  });

  it('labels the button with the member name and shows her initials', async () => {
    const { wrapper } = await openMenu();

    expect(wrapper.find('button').attributes('aria-label')).toBe('Mon compte, Aminata Diallo');
    expect(wrapper.find('button').text()).toBe('AD');
    wrapper.unmount();
  });

  it('shows her photo when she has one', async () => {
    const { wrapper } = await openMenu({ photo_url: '/api/members/1/photo?v=1' });

    expect(wrapper.find('button img').attributes('src')).toBe('/api/members/1/photo?v=1');
    expect(wrapper.find('button').text()).toBe('');
    wrapper.unmount();
  });

  it('shows the identity and role in the menu', async () => {
    const { wrapper } = await openMenu();

    // q-menu est rendu dans le body (téléportation)
    expect(document.body.textContent).toContain('Aminata Diallo');
    expect(document.body.textContent).toContain('Administratrice');
    wrapper.unmount();
  });

  it('links to my messages with the unread count', async () => {
    const { wrapper } = await openMenu();
    useInbox().unreadMessages = 4;
    await flushPromises();

    const item = Array.from(document.body.querySelectorAll<HTMLElement>('.q-item')).find((entry) =>
      entry.textContent?.includes('Mes messages')
    );
    expect(item?.getAttribute('href')).toBe('/messages');
    expect(item?.textContent).toContain('4');
    wrapper.unmount();
  });

  it('logs out and goes back to the login screen', async () => {
    const { wrapper, router } = await openMenu();

    const logoutItem = Array.from(document.body.querySelectorAll<HTMLElement>('.q-item')).find(
      (item) => item.textContent?.includes('Se déconnecter')
    );
    logoutItem?.click();
    await flushPromises();

    expect(auth.logout).toHaveBeenCalledTimes(1);
    expect(useSession().authenticated).toBe(false);
    expect(router.currentRoute.value.name).toBe('login');
    wrapper.unmount();
  });
});
