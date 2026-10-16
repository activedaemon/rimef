// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

import * as auth from '@modules/auth/services/auth';

vi.mock('@modules/auth/services/auth', () => ({
  fetchCurrentUser: vi.fn(),
  login: vi.fn(),
  logout: vi.fn(),
}));

async function visit(path: string, roles: string[]) {
  vi.mocked(auth.fetchCurrentUser).mockResolvedValue({
    id: 1,
    first_name: 'Aminata',
    last_name: 'Diallo',
    slug: 'aminata-diallo',
    name: 'Aminata Diallo',
    email: 'aminata@example.org',
    roles,
    photo_url: null,
  } as auth.AuthenticatedUser);
  vi.resetModules();
  const { default: router } = await import('./index');
  await router.push(path);
  return router.currentRoute.value.fullPath;
}

describe('router', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('shows her own profile to a member', async () => {
    expect(await visit('/profil', ['member'])).toBe('/profil');
  });

  it('opens the profile edition of an administration account without profile', async () => {
    expect(await visit('/profil', ['admin'])).toBe('/profil/modifier');
  });
});
