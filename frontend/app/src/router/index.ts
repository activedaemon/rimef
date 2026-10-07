import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';

const APP_NAME = 'RIMEF';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('@modules/home/views/HomeView.vue'),
        meta: { title: 'Accueil' },
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
