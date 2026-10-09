<script setup lang="ts">
// Accueil (maquette) : bandeau, événement à la une et citation, trois blocs
// (rendez-vous, nouveautés, ressources), bandeau éditorial. Contenu : GET /api/home.
import { onMounted, ref } from 'vue';

import QuoteBlock from '@/components/QuoteBlock.vue';
import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import { useSession } from '@/stores/session';
import EditorialBand from '../components/EditorialBand.vue';
import FeaturedEventCard from '../components/FeaturedEventCard.vue';
import HomeHero from '../components/HomeHero.vue';
import NetworkNewsCard from '../components/NetworkNewsCard.vue';
import ResourcesCard from '../components/ResourcesCard.vue';
import UpcomingMeetingsCard from '../components/UpcomingMeetingsCard.vue';
import { fetchHomeFeed, type HomeFeed } from '../services/home-feed';

const session = useSession();
const notify = useNotify();
const feed = ref<HomeFeed | null>(null);

onMounted(async () => {
  try {
    feed.value = await fetchHomeFeed();
  } catch (error) {
    notify.error(extractApiError(error, 'L’accueil n’a pas pu être chargé. Réessayez.'));
  }
});
</script>

<template>
  <q-page class="home">
    <HomeHero :first-name="session.user?.first_name ?? ''" />

    <div v-if="feed" class="container home__main">
      <!-- Sans événement à la une, la citation prend toute la largeur -->
      <div
        class="home__feature-row"
        :class="{ 'home__feature-row--quote-only': !feed.featuredEvent }"
      >
        <FeaturedEventCard v-if="feed.featuredEvent" :event="feed.featuredEvent" />
        <QuoteBlock
          :variant="feed.featuredEvent ? 'card' : 'banner'"
          tone="ivory"
          text="Des processus de paix inclusifs, pour une paix plus durable."
        />
      </div>

      <div class="home__blocks">
        <UpcomingMeetingsCard :meetings="feed.meetings" />
        <NetworkNewsCard :news="feed.news" />
        <ResourcesCard class="home__last-block" />
      </div>

      <EditorialBand class="home__band" />
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
// Contenu sous le bandeau, sur fond paper (maquette)
.home {
  padding-bottom: 44px;
  background: var(--paper);

  &__main {
    padding-top: 28px;
  }

  &__feature-row {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
    gap: 24px;

    &--quote-only {
      grid-template-columns: 1fr;
    }
  }

  &__blocks {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 24px;
    margin-top: 24px;
  }

  &__band {
    margin-top: 32px;
  }
}

@media (max-width: 1079px) {
  .home {
    &__feature-row {
      grid-template-columns: 1fr;
    }

    &__blocks {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    &__last-block {
      grid-column: 1 / -1;
    }
  }
}

@media (max-width: 719px) {
  .home {
    padding-bottom: 32px;

    &__main {
      padding-top: 20px;
    }

    &__feature-row {
      gap: 16px;
    }

    &__blocks {
      grid-template-columns: 1fr;
      gap: 16px;
      margin-top: 16px;
    }

    &__band {
      margin-top: 24px;
    }
  }
}
</style>
