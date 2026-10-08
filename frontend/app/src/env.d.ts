/// <reference types="vite/client" />

import 'vue-router';

import type { SearchScope } from '@/lib/search';

declare module 'vue-router' {
  interface RouteMeta {
    /** Titre de l'onglet du navigateur, suffixé par le nom de l'application. */
    title?: string;
    /** Accessible sans connexion (ex. page introuvable). */
    public?: boolean;
    /** Réservée aux visiteuses non connectées (connexion, mot de passe oublié…). */
    guestOnly?: boolean;
    /** Texte d'invite de la recherche du bandeau sur cette page. */
    searchPlaceholder?: string;
    /** Rubrique transmise à la page de résultats. */
    searchScope?: SearchScope;
  }
}
