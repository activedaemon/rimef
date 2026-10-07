import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '',
    name: 'home',
    component: () => import('./views/HomeView.vue'),
    meta: { title: 'Accueil' },
  },
];

export default routes;
