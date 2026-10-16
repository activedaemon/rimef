import { describe, expect, it } from 'vitest';

import {
  activityLabel,
  expiryLabel,
  lastLoginLabel,
  queryFromRoute,
  routeFromQuery,
  sortedRoles,
} from './accounts';

describe('accounts service', () => {
  it('reads and writes the list state in French in the address', () => {
    const query = queryFromRoute({
      q: 'diallo',
      statut: ['pending', 'expired'],
      role: 'admin',
      tri: 'connexion',
      page: '2',
      par_page: '24',
    });

    expect(query).toEqual({
      q: 'diallo',
      selected: { status: ['pending', 'expired'], role: ['admin'] },
      sort: 'last_login',
      page: 2,
      perPage: 24,
    });
    expect(routeFromQuery(query)).toEqual({
      q: 'diallo',
      statut: ['pending', 'expired'],
      role: ['admin'],
      tri: 'connexion',
      page: '2',
      par_page: '24',
    });
    expect(routeFromQuery(queryFromRoute({}))).toEqual({});
  });

  it('labels the last login', () => {
    const now = new Date(2026, 9, 15, 10, 30);
    const at = (date: Date) => ({ status: 'active' as const, last_login_at: date.toISOString() });

    expect(lastLoginLabel(at(new Date(2026, 9, 15, 7, 48)), now)).toBe('Aujourd’hui, 07:48');
    expect(lastLoginLabel(at(new Date(2026, 9, 14, 9, 5)), now)).toBe('Hier, 09:05');
    expect(lastLoginLabel(at(new Date(2026, 9, 11, 9, 5)), now)).toBe('Il y a 4 jours');
    expect(lastLoginLabel(at(new Date(2026, 8, 19, 9, 5)), now)).toBe('19 sept. 2026');
    expect(lastLoginLabel({ status: 'active', last_login_at: null }, now)).toBe('Aucune connexion');
    expect(lastLoginLabel({ status: 'pending', last_login_at: null }, now)).toBe('—');
  });

  it('orders the roles from the widest', () => {
    expect(sortedRoles(['member', 'admin'])).toEqual(['admin', 'member']);
  });

  it('tells the activity according to the status', () => {
    const now = new Date(2026, 9, 16, 10, 30);
    const invitation = {
      status: 'pending' as const,
      last_login_at: null,
      invited_at: new Date(2026, 9, 16, 9, 0).toISOString(),
    };

    expect(activityLabel(invitation, now)).toBe('Envoyée le 16 oct.');
    expect(
      activityLabel(
        {
          status: 'inactive',
          last_login_at: new Date(2026, 8, 19).toISOString(),
          invited_at: null,
        },
        now
      )
    ).toBe('Dernière connexion le 19 sept.');
  });

  it('counts the days before the invitation expires', () => {
    const now = new Date(2026, 9, 16, 10, 30);
    const at = (day: number, status: 'pending' | 'expired' = 'pending') => ({
      status,
      invitation_expires_at: new Date(2026, 9, day, 9, 0).toISOString(),
    });

    expect(expiryLabel(at(23), now)).toEqual({ text: 'Expire dans 7 jours', urgent: false });
    expect(expiryLabel(at(17), now)).toEqual({ text: 'Expire demain', urgent: true });
    expect(expiryLabel(at(16), now)).toEqual({ text: 'Expire aujourd’hui', urgent: true });
    expect(expiryLabel(at(13, 'expired'), now)).toEqual({
      text: 'Expirée depuis 3 jours',
      urgent: false,
    });
    expect(expiryLabel({ status: 'active', invitation_expires_at: null }, now)).toBeNull();
  });
});
