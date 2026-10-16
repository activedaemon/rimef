import { describe, expect, it } from 'vitest';

import {
  FOOTER_NAVIGATION,
  isNavigationItemActive,
  MAIN_NAVIGATION,
} from './navigation';

describe('navigation', () => {
  it('lists the four sections of the network in the main navigation', () => {
    expect(MAIN_NAVIGATION.map((item) => item.label)).toEqual([
      'Accueil',
      'Réseau',
      'Agenda',
      'Ressources',
    ]);
  });

  it('lists the information pages in the footer', () => {
    expect(FOOTER_NAVIGATION.map((item) => item.label)).toEqual([
      'À propos',
      'Contact',
      'Mentions légales',
      'Confidentialité',
    ]);
  });

  it('marks a section active on its own route only', () => {
    const network = MAIN_NAVIGATION[1]!;

    expect(isNavigationItemActive(network, 'network')).toBe(true);
    expect(isNavigationItemActive(network, 'agenda')).toBe(false);
    expect(isNavigationItemActive(network, undefined)).toBe(false);
  });

  it('keeps a section active on its detail pages', () => {
    expect(isNavigationItemActive(MAIN_NAVIGATION[1]!, 'member')).toBe(true);
  });
});
