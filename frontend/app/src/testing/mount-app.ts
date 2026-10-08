// Montage de composants pour les tests (happy-dom) : Quasar, Pinia et un routeur en mémoire.
import { mount, type ComponentMountingOptions } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { IconSet, Notify, Quasar } from 'quasar';
import type { Component } from 'vue';
import { createMemoryHistory, createRouter, RouterView, type RouteRecordRaw } from 'vue-router';

import { tablerIconMapFn } from '@/lib/tabler-icon-set';

// Mêmes icônes que l'application (main.ts)
IconSet.iconMapFn = tablerIconMapFn;

const Blank = { template: '<div />' };

const ROUTE_NAMES = [
  ['/', 'home'],
  ['/recherche', 'search'],
  ['/connexion', 'login'],
] as const;

// Rubriques avec leur recherche, comme dans router/index.ts
const SECTION_ROUTES: RouteRecordRaw[] = [
  {
    path: '/reseau',
    name: 'network',
    component: Blank,
    meta: { searchPlaceholder: 'Rechercher une médiatrice…', searchScope: 'reseau' },
  },
  {
    path: '/agenda',
    name: 'agenda',
    component: Blank,
    meta: { searchPlaceholder: 'Rechercher un événement…', searchScope: 'agenda' },
  },
  {
    path: '/ressources',
    name: 'resources',
    component: Blank,
    meta: { searchPlaceholder: 'Rechercher une ressource…', searchScope: 'ressources' },
  },
];

// Même imbrication que router/index.ts pour le profil
const PROFILE_ROUTES: RouteRecordRaw = {
  path: '/profil',
  component: RouterView,
  children: [
    { path: '', name: 'profile', component: Blank },
    { path: 'modifier', name: 'profile-edit', component: Blank },
  ],
};

export async function mountApp<T extends Component>(
  component: T,
  { path = '/', ...options }: ComponentMountingOptions<T> & { path?: string } = {}
) {
  const pinia = createPinia();
  setActivePinia(pinia);
  const routes: RouteRecordRaw[] = [
    ...ROUTE_NAMES.map(([routePath, name]) => ({ path: routePath, name, component: Blank })),
    ...SECTION_ROUTES,
    PROFILE_ROUTES,
  ];
  const router = createRouter({ history: createMemoryHistory(), routes });
  await router.push(path);
  await router.isReady();

  const wrapper = mount(component, {
    ...options,
    global: { plugins: [[Quasar, { plugins: { Notify } }], pinia, router], ...options.global },
    attachTo: document.body,
  } as ComponentMountingOptions<T>);

  return { wrapper, router, pinia };
}
