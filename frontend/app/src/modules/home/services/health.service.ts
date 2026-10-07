import { isAxiosError } from 'axios';

import { http } from '@core/services/http';

export interface HealthResponse {
  status: 'ok' | 'degraded';
  app: string;
  context: 'tenant' | 'central';
  tenant: string | null;
  database: {
    status: 'ok' | 'down';
    name: string;
  };
}

export interface HealthSummary {
  state: 'ok' | 'degraded' | 'unreachable';
  label: string;
  detail: string;
}

export async function fetchHealth(): Promise<HealthResponse> {
  try {
    const { data } = await http.get<HealthResponse>('/health');
    return data;
  } catch (error) {
    // L'API répond 503 avec le même corps quand la base est indisponible
    if (isAxiosError<HealthResponse>(error) && error.response?.status === 503) {
      return error.response.data;
    }
    throw error;
  }
}

export function summarizeHealth(health: HealthResponse | null): HealthSummary {
  if (health === null) {
    return {
      state: 'unreachable',
      label: 'API injoignable',
      detail: 'Le serveur ne répond pas. Réessayez dans un instant.',
    };
  }

  const where = health.context === 'tenant' ? `espace ${health.tenant}` : 'espace central';

  if (health.status === 'ok') {
    return {
      state: 'ok',
      label: 'API opérationnelle',
      detail: `${where} · base ${health.database.name}`,
    };
  }

  return {
    state: 'degraded',
    label: 'API dégradée',
    detail: `${where} · base de données indisponible`,
  };
}
