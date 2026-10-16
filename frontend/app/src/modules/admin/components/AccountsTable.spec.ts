// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { Account } from '../services/accounts';
import AccountsTable from './AccountsTable.vue';

const ACCOUNT = {
  id: 7,
  name: 'Aminata Diallo',
  email: 'aminata@example.org',
  photo_url: null,
  roles: ['member'],
  status: 'active',
  is_protected: false,
  is_self: false,
  last_login_at: null,
  created_at: '2026-09-12T09:00:00Z',
  member_since: '2026-09-12T09:00:00Z',
  profile_completion: { percent: 75, missing: ['photo', 'liens'] },
  invitation_expires_at: null,
} as unknown as Account;

describe('AccountsTable', () => {
  it('opens the account from a click anywhere on the row', async () => {
    const { wrapper, router } = await mountApp(AccountsTable, {
      path: '/administration',
      props: { accounts: [ACCOUNT] },
    });

    await wrapper.find('.accounts-table__row td:nth-child(3)').trigger('click');
    await flushPromises();

    expect(router.currentRoute.value.fullPath).toBe('/administration/membres/7');
  });

  it('shows the completion of the profile', async () => {
    const { wrapper } = await mountApp(AccountsTable, {
      path: '/administration',
      props: { accounts: [ACCOUNT] },
    });

    expect(wrapper.find('.accounts-table__completion').text()).toContain('75 %');
    wrapper.unmount();
  });
});
