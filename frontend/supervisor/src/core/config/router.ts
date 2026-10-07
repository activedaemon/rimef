import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';

import dashboardRoutes from '@modules/dashboard/router';

const APP_NAME = 'RIMEF Supervision';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@core/layouts/SupervisorLayout.vue'),
    children: [...dashboardRoutes],
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
