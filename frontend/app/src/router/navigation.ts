// Liens de navigation, déclarés une seule fois : en-tête, tiroir mobile,
// barre d'onglets et pied de page s'en servent. Les entrées du compte sont dans
// useAccountNavigation.

export interface NavigationItem {
  label: string;
  routeName: string;
  /** Icône Tabler (sans le préfixe ti-). */
  icon: string;
  /** Routes qui rendent le lien actif dans le tiroir (par défaut : routeName seule). */
  activeFor?: string[];
}

export const MAIN_NAVIGATION: NavigationItem[] = [
  { label: 'Accueil', routeName: 'home', icon: 'home' },
  { label: 'Réseau', routeName: 'network', icon: 'users', activeFor: ['network', 'member'] },
  { label: 'Agenda', routeName: 'agenda', icon: 'calendar', activeFor: ['agenda', 'event'] },
  { label: 'Ressources', routeName: 'resources', icon: 'book' },
];

/** Pied de page : pages d'information. */
export const FOOTER_NAVIGATION: NavigationItem[] = [
  { label: 'À propos', routeName: 'about', icon: 'info-circle' },
  { label: 'Contact', routeName: 'contact', icon: 'mail' },
  { label: 'Mentions légales', routeName: 'legal-notice', icon: 'scale' },
  { label: 'Confidentialité', routeName: 'privacy', icon: 'shield-lock' },
];

/** Lien courant dans le tiroir mobile (les q-route-tab gèrent seuls leur état actif). */
export function isNavigationItemActive(item: NavigationItem, routeName: unknown): boolean {
  return typeof routeName === 'string' && (item.activeFor ?? [item.routeName]).includes(routeName);
}
