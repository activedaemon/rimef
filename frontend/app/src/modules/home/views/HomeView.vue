<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';

import { useSession } from '@/stores/session';

const session = useSession();
const router = useRouter();
const loggingOut = ref(false);

// Provisoire : la déconnexion passera dans le menu du compte (navigation, étape C)
async function logout(): Promise<void> {
  loggingOut.value = true;
  try {
    await session.logout();
  } finally {
    loggingOut.value = false;
    await router.replace({ name: 'login' });
  }
}
</script>

<template>
  <q-page class="home">
    <section class="container home__intro">
      <p class="eyebrow">Réseau International des Femmes Médiatrices Francophones</p>
      <h1>Bonjour, {{ session.user?.first_name }}</h1>
      <p class="home__lead">
        L’espace des membres se construit : réseau, agenda des rencontres et bibliothèque de
        ressources arrivent bientôt.
      </p>
      <q-btn
        outline
        color="primary"
        no-caps
        icon="logout"
        label="Se déconnecter"
        :loading="loggingOut"
        @click="logout"
      />
    </section>
  </q-page>
</template>

<style lang="scss" scoped>
.home {
  padding-block: var(--s-10) var(--s-16);
  background: var(--paper);

  &__intro {
    display: grid;
    justify-items: start;
    gap: var(--s-4);
  }

  &__lead {
    max-width: 58ch;
    font-size: var(--fs-lead);
    color: var(--ink-2);
  }
}
</style>
