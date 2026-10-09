import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';

const APP_NAME = 'RIMeF Supervision';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/SupervisorLayout.vue'),
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@modules/dashboard/views/DashboardView.vue'),
        meta: { title: 'Tableau de bord' },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@modules/errors/views/NotFoundView.vue'),
    meta: { title: 'Page introuvable' },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: (_to, _from, savedPosition) => savedPosition ?? { top: 0 },
});

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · ${APP_NAME}` : APP_NAME;
});

export default router;
