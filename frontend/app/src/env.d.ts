/// <reference types="vite/client" />

import 'vue-router';

declare module 'vue-router' {
  interface RouteMeta {
    /** Titre de l'onglet du navigateur, suffixé par le nom de l'application. */
    title?: string;
    /** Accessible sans connexion (ex. page introuvable). */
    public?: boolean;
    /** Réservée aux visiteuses non connectées (connexion, mot de passe oublié…). */
    guestOnly?: boolean;
  }
}
