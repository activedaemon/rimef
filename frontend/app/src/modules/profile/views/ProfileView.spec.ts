// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { AuthenticatedUser } from '@modules/auth/services/auth';
import * as members from '@modules/network/services/members';
import { useSession } from '@/stores/session';
import { mountApp } from '@/testing/mount-app';
import ProfileView from './ProfileView.vue';

vi.mock('@modules/network/services/members', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@modules/network/services/members')>()),
  fetchMemberProfile: vi.fn(),
}));

const SUPERADMIN: AuthenticatedUser = {
  id: 1,
  first_name: 'David',
  last_name: 'Gautier',
  slug: 'david-gautier',
  name: 'David Gautier',
  email: 'superadmin@example.org',
  roles: ['superadmin'],
  photo_url: null,
};

async function mountAs(user: AuthenticatedUser) {
  const { wrapper } = await mountApp(ProfileView, {
    path: '/profil',
    global: { stubs: { QPage: { template: '<div><slot /></div>' } } },
  });
  useSession().user = user;
  await flushPromises();
  return wrapper;
}

describe('ProfileView', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.mocked(members.fetchMemberProfile).mockClear();
  });

  it('montre au superadmin un en-tête de fiche avec ses rôles et une carte Compte', async () => {
    const wrapper = await mountAs(SUPERADMIN);

    expect(wrapper.find('h1').text()).toBe('David Gautier');
    expect(wrapper.findAll('.rimef-badge').map((badge) => badge.text())).toEqual([
      'Superadministrateur',
    ]);
    expect(wrapper.find('.profile-header__role').text()).toBe('Équipe RIMeF');
    expect(wrapper.find('.account-card').text()).toContain('superadmin@example.org');
    expect(wrapper.text()).toContain('n’apparaît pas dans l’annuaire');
    expect(members.fetchMemberProfile).not.toHaveBeenCalled();
  });

  it('affiche tous les rôles du compte', async () => {
    const wrapper = await mountAs({ ...SUPERADMIN, roles: ['admin', 'superadmin'] });

    expect(wrapper.findAll('.rimef-badge').map((badge) => badge.text())).toEqual([
      'Superadministrateur',
      'Administratrice',
    ]);
  });

  it('montre sa fiche de l’annuaire à une médiatrice', async () => {
    vi.mocked(members.fetchMemberProfile).mockResolvedValue({
      id: 7,
      slug: 'aminata-diallo',
      name: 'Aminata Diallo',
      first_name: 'Aminata',
      photo_url: null,
      country: null,
      city: null,
      organization: null,
      job_title: null,
      tagline: null,
      bio: null,
      years_of_experience: null,
      audiences: null,
      is_available: false,
      is_favorite: false,
      conversation: null,
      expertises: [],
      zones: [],
      upcoming_events: [],
      resources: [],
    });

    const wrapper = await mountAs({
      ...SUPERADMIN,
      id: 7,
      name: 'Aminata Diallo',
      slug: 'aminata-diallo',
      roles: ['member'],
    });

    expect(members.fetchMemberProfile).toHaveBeenCalledWith(
      'aminata-diallo',
      expect.any(AbortSignal)
    );
    expect(wrapper.find('h1').text()).toBe('Aminata Diallo');
    expect(wrapper.find('.account-card').exists()).toBe(false);
  });
});
