// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { AxiosError, AxiosHeaders } from 'axios';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useSession } from '@/stores/session';
import { mountApp } from '@/testing/mount-app';
import * as members from '@modules/network/services/members';
import * as service from '../services/profile';
import type { EditableProfile, ProfileOptions } from '../services/profile';
import ProfileEditView from './ProfileEditView.vue';

vi.mock('../services/profile', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/profile')>()),
  fetchEditableProfile: vi.fn(),
  updateProfile: vi.fn(),
  uploadProfilePhoto: vi.fn(),
  deleteProfilePhoto: vi.fn(),
}));
vi.mock('@modules/network/services/members', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@modules/network/services/members')>()),
  fetchMemberProfile: vi.fn().mockRejectedValue(new Error('hors test')),
}));
vi.mock('@modules/auth/services/auth', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@modules/auth/services/auth')>()),
  fetchCurrentUser: vi.fn().mockResolvedValue(null),
}));

const OPTIONS: ProfileOptions = {
  countries: [
    { code: 'ML', name: 'Mali' },
    { code: 'SN', name: 'Sénégal' },
  ],
  organization_types: [{ value: 'civil_society', label: 'Société civile' }],
  expertises: [
    { name: 'Médiation communautaire', count: 4 },
    { name: 'Gouvernance locale', count: 2 },
  ],
};

const AMINATA: EditableProfile = {
  has_sheet: true,
  first_name: 'Aminata',
  last_name: 'Diallo',
  photo_url: null,
  job_title: 'Conseillère en paix et sécurité',
  organization_type: 'civil_society',
  organization_name: null,
  country_code: 'SN',
  city: 'Dakar',
  tagline: null,
  bio: 'Médiatrice en Afrique de l’Ouest.',
  experience_since: 2011,
  audiences: null,
  is_available: false,
  expertises: ['Médiation communautaire'],
  zones: ['SN'],
  links: {
    linkedin: null,
    website: null,
    organization: null,
    other: { label: null, url: null },
  },
  updated_at: '2026-09-12T10:00:00Z',
};

async function openEditor(profile: EditableProfile) {
  vi.mocked(service.fetchEditableProfile).mockResolvedValue({ profile, options: OPTIONS });
  const result = await mountApp(ProfileEditView, {
    path: '/profil/modifier',
    global: { stubs: { QPage: { template: '<div><slot /></div>' } } },
  });
  const session = useSession();
  session.user = { slug: 'aminata-diallo' } as typeof session.user;
  await flushPromises();
  return result;
}

function sectionTitles(wrapper: Awaited<ReturnType<typeof openEditor>>['wrapper']): string[] {
  return wrapper.findAll('.edit-sec h2').map((title) => title.text());
}

function saveButton(wrapper: Awaited<ReturnType<typeof openEditor>>['wrapper']) {
  return wrapper.findAll('button').find((button) => button.text() === 'Enregistrer')!;
}

describe('ProfileEditView', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.mocked(service.updateProfile).mockReset();
    vi.mocked(members.fetchMemberProfile).mockClear();
  });

  it('shows the sections of the mockup, without languages', async () => {
    const { wrapper } = await openEditor(AMINATA);

    expect(sectionTitles(wrapper)).toEqual([
      'Photo',
      'Identité',
      'Biographie',
      'Pratique',
      'Domaines d’expertise',
      'Zones d’intervention',
      'Liens',
    ]);
    expect(wrapper.find('.profile-preview').exists()).toBe(true);
    expect(wrapper.text()).toContain('Dernière mise à jour le 12 septembre 2026');
    wrapper.unmount();
  });

  it('limits an administration account to its photo, first and last name', async () => {
    const { wrapper } = await openEditor({ ...AMINATA, has_sheet: false });

    expect(sectionTitles(wrapper)).toEqual(['Photo', 'Identité']);
    expect(wrapper.text()).toContain('dans la messagerie et dans l’administration');
    expect(wrapper.find('#job-title').exists()).toBe(false);
    expect(wrapper.find('.profile-preview').exists()).toBe(false);
    expect(wrapper.text()).toContain('n’apparaît pas dans l’annuaire');
    // Pas de « Mon profil » à retrouver : /profil ramène ici
    expect(wrapper.text()).not.toContain('Retour à mon profil');
    expect(wrapper.find('.edit-head__actions').exists()).toBe(false);
    expect(wrapper.find('.edit-foot__actions').exists()).toBe(true);
    wrapper.unmount();
  });

  it('enables saving once something changed, and sends empty texts as null', async () => {
    const { wrapper } = await openEditor(AMINATA);
    expect(saveButton(wrapper).attributes('disabled')).toBeDefined();

    await wrapper.find('#city').setValue('  ');
    await wrapper.find('#last-name').setValue('Sow ');
    expect(saveButton(wrapper).attributes('disabled')).toBeUndefined();

    vi.mocked(service.updateProfile).mockResolvedValue({
      ...AMINATA,
      last_name: 'Sow',
      city: null,
    });
    await saveButton(wrapper).trigger('click');
    await flushPromises();

    expect(service.updateProfile).toHaveBeenCalledWith(
      expect.objectContaining({ last_name: 'Sow', city: null, zones: ['SN'] })
    );
    expect(saveButton(wrapper).attributes('disabled')).toBeDefined();
    wrapper.unmount();
  });

  it('shows the errors of the API next to their field', async () => {
    const { wrapper } = await openEditor(AMINATA);
    await wrapper.find('#city').setValue('Thiès');

    vi.mocked(service.updateProfile).mockRejectedValue(
      new AxiosError('invalid', '422', undefined, undefined, {
        status: 422,
        statusText: 'Unprocessable',
        headers: {},
        config: { headers: new AxiosHeaders() },
        data: {
          errors: {
            'links.website': ['Cette adresse ne semble pas valide.'],
            'expertises.0': ['Ce domaine d’expertise ne fait pas partie de la liste du réseau.'],
          },
        },
      })
    );
    await saveButton(wrapper).trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Cette adresse ne semble pas valide.');
    expect(wrapper.text()).toContain('ne fait pas partie de la liste du réseau');
    wrapper.unmount();
  });

  it('suggests the most common expertises not chosen yet, and adds them', async () => {
    const { wrapper } = await openEditor(AMINATA);

    const suggestions = wrapper.findAll('.expertises-field__suggestion');
    expect(suggestions.map((chip) => chip.text())).toEqual(['Gouvernance locale']);

    await suggestions[0]!.trigger('click');
    expect(wrapper.findAll('.expertises-field__chip').map((chip) => chip.text())).toEqual([
      'Médiation communautaire',
      'Gouvernance locale',
    ]);
    wrapper.unmount();
  });
});
