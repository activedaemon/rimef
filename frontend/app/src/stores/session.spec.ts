import { AxiosError, AxiosHeaders } from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import * as auth from '@modules/auth/services/auth';
import { useSession } from './session';

vi.mock('@modules/auth/services/auth', () => ({
  fetchCurrentUser: vi.fn(),
  login: vi.fn(),
  logout: vi.fn(),
}));

const aminata: auth.AuthenticatedUser = {
  id: 1,
  first_name: 'Aminata',
  last_name: 'Diallo',
  slug: 'aminata-diallo',
  name: 'Aminata Diallo',
  email: 'aminata@example.org',
  roles: ['member'],
};

function httpError(status: number): AxiosError {
  const config = { headers: new AxiosHeaders() };
  return new AxiosError('Erreur', undefined, config, undefined, {
    status,
    statusText: '',
    headers: {},
    config,
    data: {},
  });
}

const credentials = {
  email: 'aminata@example.org',
  password: 'mot-de-passe-solide',
  remember: false,
};

describe('useSession', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.resetAllMocks();
  });

  it('restores the open session only once at startup', async () => {
    vi.mocked(auth.fetchCurrentUser).mockResolvedValue(aminata);
    const session = useSession();

    await Promise.all([session.bootstrap(), session.bootstrap()]);

    expect(auth.fetchCurrentUser).toHaveBeenCalledTimes(1);
    expect(session.authenticated).toBe(true);
    expect(session.initials).toBe('AD');
  });

  it('stays logged out when the session check fails', async () => {
    vi.mocked(auth.fetchCurrentUser).mockRejectedValue(new Error('réseau'));
    const session = useSession();

    await session.bootstrap();

    expect(session.bootstrapped).toBe(true);
    expect(session.authenticated).toBe(false);
  });

  it('loads the member after login', async () => {
    vi.mocked(auth.fetchCurrentUser).mockResolvedValue(aminata);
    const session = useSession();

    await session.login(credentials);

    expect(auth.login).toHaveBeenCalledWith(credentials);
    expect(session.user).toEqual(aminata);
    expect(session.isAdmin).toBe(false);
  });

  it('takes over an already open session when login returns 409', async () => {
    vi.mocked(auth.login).mockRejectedValue(httpError(409));
    vi.mocked(auth.fetchCurrentUser).mockResolvedValue(aminata);
    const session = useSession();

    await session.login(credentials);

    expect(session.authenticated).toBe(true);
  });

  it('rethrows other login errors without loading a member', async () => {
    vi.mocked(auth.login).mockRejectedValue(httpError(422));
    const session = useSession();

    await expect(session.login(credentials)).rejects.toBeInstanceOf(AxiosError);
    expect(auth.fetchCurrentUser).not.toHaveBeenCalled();
    expect(session.authenticated).toBe(false);
  });

  it.each([
    [['superadmin'], 'Superadministrateur', true],
    [['admin'], 'Administratrice', true],
    [['member'], 'Membre RIMeF', false],
  ])('labels the roles %j as « %s »', async (roles, label, isAdmin) => {
    vi.mocked(auth.fetchCurrentUser).mockResolvedValue({ ...aminata, roles });
    const session = useSession();

    await session.bootstrap();

    expect(session.roleLabel).toBe(label);
    expect(session.isAdmin).toBe(isAdmin);
  });

  it('forgets the member on logout even if the API call fails', async () => {
    vi.mocked(auth.fetchCurrentUser).mockResolvedValue(aminata);
    vi.mocked(auth.logout).mockRejectedValue(httpError(500));
    const session = useSession();
    await session.login(credentials);

    await expect(session.logout()).rejects.toBeInstanceOf(AxiosError);

    expect(session.authenticated).toBe(false);
  });
});
