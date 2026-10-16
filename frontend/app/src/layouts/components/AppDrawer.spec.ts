// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';
import { QLayout } from 'quasar';

import type { AuthenticatedUser } from '@modules/auth/services/auth';
import { useInbox } from '@/stores/inbox';
import { useSession } from '@/stores/session';
import { mountApp } from '@/testing/mount-app';
import AppDrawer from './AppDrawer.vue';

// q-drawer doit être placé dans un q-layout ; tiroir ouvert
const InLayout = defineComponent({
  render: () => h(QLayout, null, () => h(AppDrawer, { modelValue: true })),
});

async function openDrawer(path: string, roles: AuthenticatedUser['roles']) {
  const result = await mountApp(InLayout, { path });
  useSession().user = {
    id: 1,
    first_name: 'Aminata',
    last_name: 'Diallo',
    slug: 'aminata-diallo',
    name: 'Aminata Diallo',
    email: 'aminata@example.org',
    roles,
    photo_url: null,
  };
  await flushPromises();
  return result;
}

function drawerItem(label: string) {
  return Array.from(document.body.querySelectorAll<HTMLElement>('.app-drawer .q-item')).find(
    (entry) => entry.textContent?.includes(label)
  );
}

describe('AppDrawer', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('marks only the current section, never the home page', async () => {
    const { wrapper } = await openDrawer('/reseau', ['member']);

    expect(drawerItem('Réseau')?.getAttribute('aria-current')).toBe('page');
    expect(drawerItem('Accueil')?.getAttribute('aria-current')).toBeNull();
    expect(drawerItem('Accueil')?.classList.contains('q-router-link--active')).toBe(false);
    wrapper.unmount();
  });

  it('shares the account entries of the account menu', async () => {
    const { wrapper } = await openDrawer('/', ['admin']);
    useInbox().unreadMessages = 3;
    await flushPromises();

    expect(drawerItem('Voir mon profil')).toBeUndefined();
    expect(drawerItem('Modifier mon profil')?.getAttribute('href')).toBe('/profil/modifier');
    expect(drawerItem('Mes messages')?.getAttribute('href')).toBe('/messages');
    expect(drawerItem('Mes messages')?.textContent).toContain('3');
    expect(drawerItem('Administration')?.getAttribute('href')).toBe('/administration');
    expect(drawerItem('Se déconnecter')).toBeDefined();
    wrapper.unmount();
  });

  it('opens the profile of a member', async () => {
    const { wrapper } = await openDrawer('/', ['member']);

    expect(drawerItem('Voir mon profil')?.getAttribute('href')).toBe('/profil');
    expect(drawerItem('Administration')).toBeUndefined();
    wrapper.unmount();
  });
});
