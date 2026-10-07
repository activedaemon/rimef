/// <reference types="vitest/config" />
import { fileURLToPath, URL } from 'node:url';

import { quasar, transformAssetUrls } from '@quasar/vite-plugin';
import vue from '@vitejs/plugin-vue';
import { defineConfig, type WatchOptions } from 'vite';

export default defineConfig({
  base: '/',
  plugins: [
    vue({
      template: { transformAssetUrls },
    }),
    quasar({
      sassVariables: fileURLToPath(new URL('./src/css/quasar.variables.scss', import.meta.url)),
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      '@modules': fileURLToPath(new URL('./src/modules', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5174,
    strictPort: true,
    hmr: {
      // En Docker, le HMR passe par Traefik (port 9280) ; surchargeable via VITE_HMR_CLIENT_PORT
      clientPort: process.env.VITE_DOCKER_ENV
        ? Number(process.env.VITE_HMR_CLIENT_PORT) || 9280
        : 5174,
    },
    watch: {
      usePolling: !!process.env.VITE_DOCKER_ENV,
    } satisfies WatchOptions,
  },
  test: {
    environment: 'node',
    include: ['src/**/*.spec.ts'],
  },
});
