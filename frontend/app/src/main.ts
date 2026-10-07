import { createApp } from 'vue';
import { IconSet, Notify, Quasar } from 'quasar';
import langFr from 'quasar/lang/fr';
import { createPinia } from 'pinia';

// Polices hébergées par l'application (font-display: swap) ; unicode-range limite
// le téléchargement aux sous-ensembles utilisés (latin pour le français)
import '@fontsource-variable/inter/wght.css';
import '@fontsource/instrument-serif/latin-400.css';
import '@fontsource/instrument-serif/latin-400-italic.css';
import '@tabler/icons-webfont/dist/tabler-icons.min.css';
import 'quasar/src/css/index.sass';
import '@/css/app.scss';

import App from '@/App.vue';
import { setUnauthorizedHandler } from '@/lib/http';
import { tablerIconMapFn } from '@/lib/tabler-icon-set';
import router from '@/router';
import { useSession } from '@/stores/session';

IconSet.iconMapFn = tablerIconMapFn;

const app = createApp(App).use(createPinia()).use(router).use(Quasar, {
  lang: langFr,
  plugins: { Notify },
});

// Session expirée pendant la navigation (401) : retour à la connexion, puis à la page en cours
setUnauthorizedHandler(() => {
  useSession().clear();
  const current = router.currentRoute.value;
  if (current.name !== 'login') {
    void router.push({ name: 'login', query: { redirect: current.fullPath } });
  }
});

app.mount('#app');
