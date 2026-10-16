// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useSession } from '@/stores/session';
import { mountApp } from '@/testing/mount-app';
import * as accounts from '../services/accounts';
import InviteDialog from './InviteDialog.vue';

vi.mock('../services/accounts', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/accounts')>()),
  inviteAccount: vi.fn(),
}));

async function openDialog(roles: string[]) {
  const result = await mountApp(InviteDialog, { props: { modelValue: false } });
  useSession().user = {
    id: 1,
    first_name: 'Hélène',
    last_name: 'Marchand',
    slug: 'helene-marchand',
    name: 'Hélène Marchand',
    email: 'helene@example.org',
    roles,
    photo_url: null,
  };
  await result.wrapper.setProps({ modelValue: true });
  await flushPromises();
  return result;
}

function fill(id: string, value: string): void {
  const input = document.body.querySelector<HTMLInputElement>(`#${id}`)!;
  input.value = value;
  input.dispatchEvent(new Event('input'));
}

const submit = () =>
  document.body.querySelector<HTMLFormElement>('.invite-dialog form')!.requestSubmit();

describe('InviteDialog', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.mocked(accounts.inviteAccount).mockReset();
  });

  it('invites a member for an administrator, without any role choice', async () => {
    vi.mocked(accounts.inviteAccount).mockResolvedValue({
      name: 'Mireille Sawadogo',
      email: 'mireille@example.org',
      invitation_expires_at: '2026-10-22T10:00:00Z',
    } as accounts.Account);
    await openDialog(['admin']);

    expect(document.body.textContent).toContain('comme membre');
    expect(document.body.querySelector('.invite-dialog fieldset')).toBeNull();

    fill('invite-first-name', 'Mireille');
    fill('invite-last-name', 'Sawadogo');
    fill('invite-email', 'mireille@example.org');
    await flushPromises();
    submit();
    await flushPromises();

    expect(accounts.inviteAccount).toHaveBeenCalledWith({
      first_name: 'Mireille',
      last_name: 'Sawadogo',
      email: 'mireille@example.org',
    });
    expect(document.body.textContent).toContain('Invitation envoyée');
  });

  it('checks the fields before sending', async () => {
    await openDialog(['admin']);

    fill('invite-email', 'mireille');
    await flushPromises();
    submit();
    await flushPromises();

    expect(accounts.inviteAccount).not.toHaveBeenCalled();
    expect(document.body.textContent).toContain('Indiquez son prénom.');
    expect(document.body.textContent).toContain('Cette adresse n’est pas valide');
  });

  it('lets the superadmin choose the roles', async () => {
    await openDialog(['superadmin']);

    expect(document.body.querySelector('.invite-dialog fieldset legend')?.textContent).toBe(
      'Rôles'
    );
  });
});
