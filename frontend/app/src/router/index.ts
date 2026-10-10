import { createRouter, createWebHistory, RouterView, type RouteRecordRaw } from 'vue-router';

import { safeRedirect } from '@/lib/redirect';
import { useSession } from '@/stores/session';

const APP_NAME = 'RIMeF';

// Tout l'espace des membres est réservé aux comptes connectés : une route est
// protégée sauf si elle (ou un parent) porte `meta.public` ou `meta.guestOnly`.
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
      {
        // Routes imbriquées : l'onglet « Réseau » reste actif sur la fiche d'une médiatrice
        path: 'reseau',
        component: RouterView,
        meta: {
          searchPlaceholder: 'Rechercher une médiatrice…',
          searchScope: 'reseau',
        },
        children: [
          {
            path: '',
            name: 'network',
            component: () => import('@modules/network/views/NetworkView.vue'),
            meta: { title: 'Réseau', searchInPage: true },
          },
          {
            path: ':slug([a-z0-9-]+)',
            name: 'member',
            component: () => import('@modules/network/views/MemberProfileView.vue'),
            meta: { title: 'Profil médiatrice' },
          },
        ],
      },
      {
        path: 'agenda',
        name: 'agenda',
        component: () => import('@modules/agenda/views/AgendaView.vue'),
        meta: {
          title: 'Agenda',
          searchPlaceholder: 'Rechercher un événement…',
          searchScope: 'agenda',
        },
      },
      {
        path: 'ressources',
        name: 'resources',
        component: () => import('@modules/resources/views/ResourcesView.vue'),
        meta: {
          title: 'Ressources',
          searchPlaceholder: 'Rechercher une ressource…',
          searchScope: 'ressources',
        },
      },
      {
        // Routes imbriquées : l'onglet « Profil » reste actif sur /profil/modifier
        path: 'profil',
        component: RouterView,
        children: [
          {
            path: '',
            name: 'profile',
            component: () => import('@modules/profile/views/ProfileView.vue'),
            meta: {
              title: 'Mon profil',
              searchPlaceholder: 'Rechercher un événement, une médiatrice…',
            },
          },
          {
            path: 'modifier',
            name: 'profile-edit',
            component: () => import('@modules/profile/views/ProfileEditView.vue'),
            meta: {
              title: 'Modifier mon profil',
              searchPlaceholder: 'Rechercher une médiatrice, un événement…',
            },
          },
        ],
      },
      {
        path: 'recherche',
        name: 'search',
        component: () => import('@modules/search/views/SearchView.vue'),
        meta: { title: 'Recherche' },
      },
      {
        path: 'a-propos',
        name: 'about',
        component: () => import('@modules/info/views/AboutView.vue'),
        meta: { title: 'À propos' },
      },
      {
        path: 'contact',
        name: 'contact',
        component: () => import('@modules/info/views/ContactView.vue'),
        meta: { title: 'Contact' },
      },
      {
        // À rendre consultable sans connexion (LCEN) quand son contenu sera rédigé
        path: 'mentions-legales',
        name: 'legal-notice',
        component: () => import('@modules/info/views/LegalNoticeView.vue'),
        meta: { title: 'Mentions légales' },
      },
      {
        // À rendre consultable sans connexion (RGPD) quand son contenu sera rédigé
        path: 'confidentialite',
        name: 'privacy',
        component: () => import('@modules/info/views/PrivacyView.vue'),
        meta: { title: 'Confidentialité' },
      },
    ],
  },
  {
    path: '/',
    component: () => import('@/layouts/AuthLayout.vue'),
    meta: { guestOnly: true },
    children: [
      {
        path: 'connexion',
        name: 'login',
        component: () => import('@modules/auth/views/LoginView.vue'),
        meta: { title: 'Connexion' },
      },
      {
        path: 'mot-de-passe-oublie',
        name: 'forgot-password',
        component: () => import('@modules/auth/views/ForgotPasswordView.vue'),
        meta: { title: 'Mot de passe oublié' },
      },
      {
        path: 'reinitialiser-mot-de-passe',
        name: 'reset-password',
        component: () => import('@modules/auth/views/ResetPasswordView.vue'),
        meta: { title: 'Nouveau mot de passe' },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@modules/errors/views/NotFoundView.vue'),
    meta: { title: 'Page introuvable', public: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: (_to, _from, savedPosition) => savedPosition ?? { top: 0 },
});

router.beforeEach(async (to) => {
  const session = useSession();
  await session.bootstrap();

  const guestOnly = to.matched.some((record) => record.meta.guestOnly);
  const isPublic = guestOnly || to.matched.some((record) => record.meta.public);

  if (!isPublic && !session.authenticated) {
    return { name: 'login', query: to.fullPath === '/' ? {} : { redirect: to.fullPath } };
  }

  if (guestOnly && session.authenticated) {
    return safeRedirect(to.query.redirect);
  }

  return true;
});

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · ${APP_NAME}` : APP_NAME;
});

export default router;
