// Montage de composants pour les tests (happy-dom) : Quasar, Pinia et un routeur en mémoire.
import { mount, type ComponentMountingOptions } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { IconSet, Quasar } from 'quasar';
import type { Component } from 'vue';
import { createMemoryHistory, createRouter, type RouteRecordRaw } from 'vue-router';

import { tablerIconMapFn } from '@/lib/tabler-icon-set';

// Mêmes icônes que l'application (main.ts)
IconSet.iconMapFn = tablerIconMapFn;

const Blank = { template: '<div />' };

const ROUTE_NAMES = [
  ['/', 'home'],
  ['/reseau', 'network'],
  ['/agenda', 'agenda'],
  ['/ressources', 'resources'],
  ['/profil', 'profile'],
  ['/profil/modifier', 'profile-edit'],
  ['/connexion', 'login'],
] as const;

export async function mountApp<T extends Component>(
  component: T,
  { path = '/', ...options }: ComponentMountingOptions<T> & { path?: string } = {}
) {
  const pinia = createPinia();
  setActivePinia(pinia);
  const routes: RouteRecordRaw[] = ROUTE_NAMES.map(([routePath, name]) => ({
    path: routePath,
    name,
    component: Blank,
  }));
  const router = createRouter({ history: createMemoryHistory(), routes });
  await router.push(path);
  await router.isReady();

  const wrapper = mount(component, {
    ...options,
    global: { plugins: [Quasar, pinia, router], ...options.global },
    attachTo: document.body,
  } as ComponentMountingOptions<T>);

  return { wrapper, router, pinia };
}
