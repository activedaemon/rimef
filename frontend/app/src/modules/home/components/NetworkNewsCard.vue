<script setup lang="ts">
// Nouveautés du réseau (maquette Accueil) : dernières médiatrices inscrites, avec leur photo
// et un lien vers leur fiche.
import MemberAvatar from '@/components/MemberAvatar.vue';
import type { NetworkNews } from '../services/home-feed';

defineProps<{ news: NetworkNews[] }>();
</script>

<template>
  <q-card flat class="rimef-card home-block" tag="section" aria-labelledby="news-title">
    <div class="card-head">
      <h2 id="news-title" class="card-title">Nouveautés du réseau</h2>
      <router-link :to="{ name: 'network' }" class="link-more">Voir tout</router-link>
    </div>
    <p v-if="!news.length" class="home-block__empty">Aucune nouveauté pour le moment.</p>
    <ul v-else class="home-block__list">
      <li v-for="item in news" :key="item.person.id" class="news">
        <MemberAvatar :name="item.person.name" :photo="item.person.photo" />
        <div>
          <p class="news__text">
            <router-link
              :to="{ name: 'member', params: { slug: item.person.slug } }"
              class="news__name"
            >
              {{ item.person.name }}
            </router-link>
            {{ item.action }}
          </p>
          <p class="news__when">{{ item.when }}</p>
        </div>
      </li>
    </ul>
  </q-card>
</template>

<style lang="scss" scoped>
@use './home-block';

.news {
  display: flex;
  align-items: center;
  gap: 12px;

  &__text {
    font-size: var(--fs-sm);
    line-height: 1.4;
    color: var(--ink-2);
  }

  &__name {
    font-weight: 600;
    color: var(--ink);
    text-decoration: none;

    &:hover,
    &:focus-visible {
      color: var(--link-hover);
    }
  }

  &__when {
    margin-top: 2px;
    font-size: var(--fs-xs);
    color: var(--muted);
  }
}
</style>
