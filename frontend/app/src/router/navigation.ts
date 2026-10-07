// Liens de navigation, déclarés une seule fois : en-tête, tiroir mobile,
// barre d'onglets et pied de page s'en servent.

export interface NavigationItem {
  label: string;
  routeName: string;
  /** Icône Tabler (sans le préfixe ti-). */
  icon: string;
  /** Routes qui rendent le lien actif (par défaut : routeName seule). */
  activeFor?: string[];
}

export const MAIN_NAVIGATION: NavigationItem[] = [
  { label: 'Accueil', routeName: 'home', icon: 'home' },
  { label: 'Réseau', routeName: 'network', icon: 'users' },
  { label: 'Agenda', routeName: 'agenda', icon: 'calendar' },
  { label: 'Ressources', routeName: 'resources', icon: 'book' },
];

export const PROFILE_NAVIGATION: NavigationItem = {
  label: 'Profil',
  routeName: 'profile',
  icon: 'user',
  activeFor: ['profile', 'profile-edit'],
};

/** Barre d'onglets mobile : navigation principale + profil. */
export const TAB_BAR_NAVIGATION: NavigationItem[] = [...MAIN_NAVIGATION, PROFILE_NAVIGATION];

export function isNavigationItemActive(item: NavigationItem, routeName: unknown): boolean {
  return typeof routeName === 'string' && (item.activeFor ?? [item.routeName]).includes(routeName);
}
