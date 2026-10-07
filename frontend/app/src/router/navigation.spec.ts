import { describe, expect, it } from 'vitest';

import {
  isNavigationItemActive,
  MAIN_NAVIGATION,
  PROFILE_NAVIGATION,
  TAB_BAR_NAVIGATION,
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

  it('adds the profile to the mobile tab bar', () => {
    expect(TAB_BAR_NAVIGATION.at(-1)).toBe(PROFILE_NAVIGATION);
  });

  it('marks a section active on its own route only', () => {
    const network = MAIN_NAVIGATION[1]!;

    expect(isNavigationItemActive(network, 'network')).toBe(true);
    expect(isNavigationItemActive(network, 'agenda')).toBe(false);
    expect(isNavigationItemActive(network, undefined)).toBe(false);
  });

  it('keeps the profile active while editing it', () => {
    expect(isNavigationItemActive(PROFILE_NAVIGATION, 'profile-edit')).toBe(true);
  });
});
