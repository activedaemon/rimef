<script setup lang="ts">
import PageIntro from '@/components/PageIntro.vue';
import { MAIN_NAVIGATION } from '@/router/navigation';
import { useSession } from '@/stores/session';

const session = useSession();

const SHORTCUTS: Record<string, string> = {
  network: 'Les médiatrices du réseau, leurs expertises et leurs langues.',
  agenda: 'Les rencontres internationales et régionales à venir.',
  resources: 'Rapports, analyses, publications et formations.',
};
const shortcuts = MAIN_NAVIGATION.filter((item) => item.routeName in SHORTCUTS);
</script>

<template>
  <q-page class="home">
    <div class="container">
      <PageIntro
        eyebrow="Réseau International des Femmes Médiatrices Francophones"
        :title="`Bonjour, ${session.user?.first_name ?? ''}`"
        lead="Retrouvez le réseau, l’agenda des rencontres et la bibliothèque de ressources."
      />

      <nav class="home__shortcuts" aria-label="Accès rapides">
        <q-card
          v-for="item in shortcuts"
          :key="item.routeName"
          flat
          bordered
          class="home__shortcut"
        >
          <router-link :to="{ name: item.routeName }" class="home__shortcut-link">
            <q-icon :name="item.icon" size="22px" class="home__shortcut-icon" />
            <span class="home__shortcut-title">{{ item.label }}</span>
            <span class="home__shortcut-text">{{ SHORTCUTS[item.routeName] }}</span>
          </router-link>
        </q-card>
      </nav>
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.home {
  padding-bottom: var(--s-16);
  background: var(--paper);

  &__shortcuts {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--s-6);
  }

  &__shortcut {
    background: var(--card);
    border-color: var(--border);
    box-shadow: var(--shadow-card);
    transition: border-color 0.15s;

    &:hover {
      border-color: var(--border-hover);
    }
  }

  &__shortcut-link {
    display: grid;
    gap: var(--s-2);
    height: 100%;
    padding: var(--s-6);
    color: inherit;
    text-decoration: none;
  }

  &__shortcut-icon {
    color: var(--petrol);
  }

  &__shortcut-title {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: var(--fs-h3);
    color: var(--ink);
  }

  &__shortcut-text {
    font-size: var(--fs-sm);
    color: var(--ink-2);
  }
}

@media (max-width: 1079px) {
  .home__shortcuts {
    grid-template-columns: 1fr;
    gap: var(--s-4);
  }
}
</style>
