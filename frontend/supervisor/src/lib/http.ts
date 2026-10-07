// Client HTTP partagé (axios).
// - baseURL relative `/api` : Traefik route `/api/*` du même domaine vers Laravel,
//   donc pas de CORS et le cookie de session Sanctum suivra automatiquement.
// - Accept / X-Requested-With : Laravel répond en JSON plutôt qu'en redirection HTML.

import axios, { type AxiosInstance } from 'axios';

export const http: AxiosInstance = axios.create({
  baseURL: '/api',
  withCredentials: true,
  timeout: 15000,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});
