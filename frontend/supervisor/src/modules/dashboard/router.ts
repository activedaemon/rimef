import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '',
    name: 'dashboard',
    component: () => import('./views/DashboardView.vue'),
    meta: { title: 'Tableau de bord' },
  },
];

export default routes;
