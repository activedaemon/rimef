import { describe, expect, it } from 'vitest';

import { summarizeHealth, type HealthResponse } from './health';

const healthy: HealthResponse = {
  status: 'ok',
  app: 'RIMeF',
  context: 'tenant',
  tenant: 'rimef',
  database: { status: 'ok', name: 'rimef_tenant_rimef' },
};

describe('summarizeHealth', () => {
  it('reports an operational tenant API with its database', () => {
    expect(summarizeHealth(healthy)).toEqual({
      state: 'ok',
      label: 'API opérationnelle',
      detail: 'espace rimef · base rimef_tenant_rimef',
    });
  });

  it('reports a degraded API when the database is down', () => {
    const summary = summarizeHealth({
      ...healthy,
      status: 'degraded',
      database: { status: 'down', name: 'rimef_tenant_rimef' },
    });

    expect(summary.state).toBe('degraded');
    expect(summary.detail).toContain('base de données indisponible');
  });

  it('names the central space when no tenant is resolved', () => {
    const summary = summarizeHealth({ ...healthy, context: 'central', tenant: null });

    expect(summary.detail).toBe('espace central · base rimef_tenant_rimef');
  });

  it('reports an unreachable API when there is no response', () => {
    expect(summarizeHealth(null).state).toBe('unreachable');
  });
});
