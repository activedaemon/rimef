/// <reference types="vite/client" />

import 'vue-router';

declare module 'vue-router' {
  interface RouteMeta {
    /** Titre de l'onglet du navigateur, suffixé par le nom de l'application. */
    title?: string;
  }
}
