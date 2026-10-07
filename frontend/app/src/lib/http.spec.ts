import { AxiosError, AxiosHeaders } from 'axios';
import { describe, expect, it } from 'vitest';

import { extractApiError } from './http';

const FALLBACK = 'Une erreur est survenue.';

function httpError(status: number, data: unknown): AxiosError {
  const config = { headers: new AxiosHeaders() };
  return new AxiosError('Erreur', undefined, config, undefined, {
    status,
    statusText: '',
    headers: {},
    config,
    data,
  });
}

describe('extractApiError', () => {
  it('returns the first validation error', () => {
    const error = httpError(422, {
      message: 'Données invalides.',
      errors: { email: ['Ces identifiants ne correspondent pas à nos enregistrements.'] },
    });

    expect(extractApiError(error, FALLBACK)).toBe(
      'Ces identifiants ne correspondent pas à nos enregistrements.'
    );
  });

  it('returns the API message when there is no validation error', () => {
    expect(
      extractApiError(httpError(403, { message: 'Votre compte est désactivé.' }), FALLBACK)
    ).toBe('Votre compte est désactivé.');
  });

  it('explains the wait after too many attempts', () => {
    expect(extractApiError(httpError(429, { message: 'Too Many Attempts.' }), FALLBACK)).toBe(
      'Trop de tentatives. Patientez une minute avant de réessayer.'
    );
  });

  it('reports an unreachable server', () => {
    const error = new AxiosError('Network Error', 'ERR_NETWORK');

    expect(extractApiError(error, FALLBACK)).toBe(
      'Le serveur ne répond pas. Vérifiez votre connexion puis réessayez.'
    );
  });

  it('falls back for an error that does not come from the API', () => {
    expect(extractApiError(new Error('inattendue'), FALLBACK)).toBe(FALLBACK);
  });
});
