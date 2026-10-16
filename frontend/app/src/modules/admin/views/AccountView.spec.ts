// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import * as accounts from '../services/accounts';
import AccountView from './AccountView.vue';

vi.mock('../services/accounts', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/accounts')>()),
  fetchAccount: vi.fn(),
  resendInvitation: vi.fn(),
}));

const NO_ACTION = {
  toggle_activation: false,
  update_roles: false,
  delete: false,
  manage_invitation: false,
  send_message: false,
};

const AMINATA: accounts.Account = {
  id: 7,
  first_name: 'Aminata',
  last_name: 'Diallo',
  name: 'Aminata Diallo',
  email: 'aminata@example.org',
  photo_url: null,
  roles: ['member'],
  status: 'active',
  profile_slug: 'aminata-diallo',
  is_protected: false,
  is_self: false,
  last_login_at: '2026-10-14T09:05:00Z',
  created_at: '2026-09-12T09:00:00Z',
  member_since: '2026-09-12T09:00:00Z',
  invited_at: null,
  invitation_expires_at: null,
  invitation_resendable_at: null,
  invited_by: null,
  profile_completion: { percent: 75, missing: ['photo', 'liens'] },
  profile_summary: {
    completion: 75,
    missing: ['photo', 'liens'],
    visible: true,
    updated_at: '2026-10-14T09:00:00Z',
    upcoming_events: 3,
    resources: 1,
  },
  can: { ...NO_ACTION, toggle_activation: true, send_message: true },
};

async function openAccount(account: accounts.Account) {
  vi.mocked(accounts.fetchAccount).mockResolvedValue(account);
  const result = await mountApp(AccountView, {
    path: '/administration/membres/7',
    global: { stubs: { QPage: { template: '<div><slot /></div>' } } },
  });
  await flushPromises();
  return result;
}

const buttons = (wrapper: Awaited<ReturnType<typeof openAccount>>['wrapper']) =>
  wrapper.findAll('button').map((button) => button.text());

describe('AccountView', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.mocked(accounts.fetchAccount).mockReset();
  });

  it('shows the facts and only the allowed actions of an administrator on a member', async () => {
    const { wrapper } = await openAccount(AMINATA);

    expect(wrapper.text()).toContain('aminata@example.org');
    expect(wrapper.text()).toContain('Voir la fiche d’Aminata');
    expect(wrapper.text()).toContain('Fiche complétée à 75 %');
    expect(wrapper.text()).toContain('3 événements à venir');
    expect(wrapper.text()).toContain('Seul le superadministrateur peut modifier les rôles.');
    expect(buttons(wrapper)).toEqual(
      expect.arrayContaining(['Écrire à Aminata', 'Désactiver le compte'])
    );
    expect(wrapper.text()).not.toContain('Actions sensibles');
    expect(wrapper.find('fieldset').exists()).toBe(false);
  });

  it('lets the superadmin edit the roles and delete', async () => {
    const { wrapper } = await openAccount({
      ...AMINATA,
      can: { ...AMINATA.can, update_roles: true, delete: true, send_message: false },
    });

    expect(wrapper.find('fieldset legend').text()).toBe('Rôles du compte');
    expect(wrapper.text()).toContain('Actions sensibles');
    expect(buttons(wrapper)).not.toContain('Écrire à Aminata');
    const save = wrapper
      .findAll('button')
      .find((button) => button.text() === 'Enregistrer les rôles')!;
    expect(save.attributes('aria-disabled')).toBe('true');
  });

  it('protects the superadmin account', async () => {
    const { wrapper } = await openAccount({
      ...AMINATA,
      roles: ['superadmin'],
      is_protected: true,
      profile_slug: null,
      can: NO_ACTION,
    });

    expect(wrapper.text()).toContain('Compte protégé');
    expect(wrapper.find('.profile-header__actions').exists()).toBe(false);
  });

  it('offers to resend or cancel a pending invitation', async () => {
    const invitation = {
      ...AMINATA,
      status: 'pending' as const,
      profile_summary: null,
      profile_slug: null,
      last_login_at: null,
      invited_at: '2026-10-13T14:05:00Z',
      invitation_expires_at: '2026-10-20T14:05:00Z',
      invited_by: 'Hélène Marchand',
      can: { ...NO_ACTION, manage_invitation: true },
    };
    vi.mocked(accounts.resendInvitation).mockResolvedValue(invitation);
    const { wrapper } = await openAccount(invitation);

    expect(wrapper.text()).toContain('par Hélène Marchand');
    expect(wrapper.text()).toContain(
      'Cette personne complétera sa fiche après avoir activé son compte.'
    );
    expect(wrapper.text()).toContain('Expire le');
    expect(wrapper.find('.account-expiry').text()).toMatch(
      /^\((dans \d+ jours|demain|aujourd’hui)\)$/
    );
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Renvoyer l’invitation')!
      .trigger('click');
    await flushPromises();
    expect(accounts.resendInvitation).toHaveBeenCalledWith(7);
  });

  it('says when the account no longer exists', async () => {
    vi.mocked(accounts.fetchAccount).mockRejectedValue({
      isAxiosError: true,
      response: { status: 404 },
    });
    const { wrapper } = await mountApp(AccountView, {
      path: '/administration/membres/7',
      global: { stubs: { QPage: { template: '<div><slot /></div>' } } },
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Compte introuvable');
  });

  it('speaks of the invitation, not of the team, for an administrator not activated yet', async () => {
    const { wrapper } = await openAccount({
      ...AMINATA,
      roles: ['admin'],
      status: 'pending',
      profile_summary: null,
      profile_slug: null,
      invited_at: '2026-10-15T14:05:00Z',
      invitation_expires_at: '2026-10-22T14:05:00Z',
      can: { ...NO_ACTION, manage_invitation: true },
    });

    expect(wrapper.text()).toContain('une fois le compte activé, il apparaîtra comme');
    expect(wrapper.text()).not.toContain('ce compte apparaît comme « Équipe RIMeF »');
  });

  it('greys out « Renvoyer l’invitation » less than 10 minutes after the last sending', async () => {
    const sent = new Date(Date.now() - 4 * 60_000);
    const resendable = new Date(sent.getTime() + 10 * 60_000);
    const { wrapper } = await openAccount({
      ...AMINATA,
      status: 'pending',
      profile_summary: null,
      profile_slug: null,
      invited_at: sent.toISOString(),
      invitation_expires_at: new Date(sent.getTime() + 7 * 86_400_000).toISOString(),
      invitation_resendable_at: resendable.toISOString(),
      can: { ...NO_ACTION, manage_invitation: true },
    });

    const resend = wrapper
      .findAll('button')
      .find((button) => button.text() === 'Renvoyer l’invitation')!;
    expect(resend.attributes('aria-disabled')).toBe('true');
    expect(wrapper.find('#resend-hint').text()).toContain('renvoi possible à partir de');
  });
});
