// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useSession } from '@/stores/session';
import { mountApp } from '@/testing/mount-app';
import * as members from '../services/members';
import MemberProfileView from './MemberProfileView.vue';

vi.mock('../services/members', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/members')>()),
  fetchMemberProfile: vi.fn(),
}));

const PROFILE = {
  id: 7,
  slug: 'aminata-diallo',
  name: 'Aminata Diallo',
  first_name: 'Aminata',
  photo_url: null,
  country: null,
  city: null,
  region: null,
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
} as members.MemberProfile;

async function openProfile(viewerId = 1) {
  const result = await mountApp(MemberProfileView, {
    path: '/reseau/aminata-diallo',
    global: { stubs: { QPage: { template: '<div><slot /></div>' } } },
  });
  const session = useSession();
  session.user = { id: viewerId } as typeof session.user;
  await flushPromises();
  return result;
}

describe('MemberProfileView', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.mocked(members.fetchMemberProfile).mockResolvedValue({ ...PROFILE });
  });

  it('shows the more actions menu of the mockup, not available yet', async () => {
    const { wrapper } = await openProfile();

    const more = wrapper.find('[aria-label="Plus d’actions"]');
    expect(more.exists()).toBe(true);
    await more.trigger('click');
    await flushPromises();

    const items = Array.from(document.body.querySelectorAll('.member-menu .q-item'));
    expect(items.map((item) => item.textContent?.trim())).toEqual([
      'Inviter à un événement',
      'Copier le lien du profil',
      'Recommander à une membre',
      'Signaler une information',
    ]);
    expect(items.every((item) => item.getAttribute('aria-disabled') === 'true')).toBe(true);
    wrapper.unmount();
  });

  it('hides the actions on her own profile', async () => {
    const { wrapper } = await openProfile(7);

    expect(wrapper.find('[aria-label="Plus d’actions"]').exists()).toBe(false);
    wrapper.unmount();
  });
});
