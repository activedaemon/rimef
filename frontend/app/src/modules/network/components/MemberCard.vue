<script setup lang="ts">
// Carte médiatrice (fiche MemberCard) : portrait, marque-page, nom, pays, deux expertises,
// prochain événement ou disponibilité. Toute la carte mène au profil (lien étiré sur le nom).
// Portrait : la photo de la médiatrice, ou ses initiales (aussi si la photo ne se charge pas).
import { computed, ref } from 'vue';

import MemberAvatar from '@/components/MemberAvatar.vue';
import type { Member } from '../services/members';

const props = defineProps<{ member: Member }>();
const emit = defineEmits<{ toggleFavorite: [] }>();

// Marque-page plein (enregistré) : tracé SVG de l'icône Tabler « bookmark », rempli par Quasar,
// plutôt que la police des icônes pleines (un fichier de plus pour une seule icône)
const BOOKMARK_FILLED = 'M18 7v14l-6 -4l-6 4v-14a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4z|0 0 24 24';

const favoriteLabel = computed(() =>
  props.member.is_favorite
    ? `Retirer ${props.member.name} des favoris`
    : `Ajouter ${props.member.name} aux favoris`
);

const MAX_EXPERTISES = 2;

const expertises = computed(() => props.member.expertises.slice(0, MAX_EXPERTISES));
const photoFailed = ref(false);
</script>

<template>
  <q-card flat tag="article" class="rimef-card member-card">
    <div class="member-card__portrait">
      <img
        v-if="member.photo_url && !photoFailed"
        :src="member.photo_url"
        alt=""
        loading="lazy"
        decoding="async"
        class="member-card__photo"
        @error="photoFailed = true"
      />
      <MemberAvatar v-else :name="member.name" :size="72" />
    </div>

    <q-btn
      flat
      dense
      :ripple="false"
      class="member-card__bookmark"
      :class="{ 'member-card__bookmark--on': member.is_favorite }"
      :icon="member.is_favorite ? BOOKMARK_FILLED : 'bookmark'"
      :aria-pressed="member.is_favorite"
      :aria-label="favoriteLabel"
      @click="emit('toggleFavorite')"
    />

    <div class="member-card__body">
      <h3 class="member-card__name">
        <router-link :to="{ name: 'member', params: { id: member.id } }">
          {{ member.name }}
        </router-link>
      </h3>
      <p v-if="member.country" class="member-card__country">{{ member.country.name }}</p>

      <ul
        v-if="expertises.length"
        class="member-card__expertises"
        aria-label="Domaines d’expertise"
      >
        <li v-for="expertise in expertises" :key="expertise">{{ expertise }}</li>
      </ul>

      <div class="member-card__foot">
        <p v-if="member.next_event" class="member-card__next">
          Prochainement :
          <b>
            <router-link :to="{ name: 'agenda' }">{{ member.next_event.title }}</router-link>
          </b>
        </p>
        <p v-else-if="member.is_available" class="member-card__available">
          Disponible pour collaboration
        </p>
        <q-icon name="arrow-right" size="17px" class="member-card__arrow" aria-hidden="true" />
      </div>
    </div>
  </q-card>
</template>

<style lang="scss" scoped>
.member-card {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  overflow: hidden;
  transition:
    box-shadow 0.2s,
    border-color 0.2s;

  &:hover {
    border-color: var(--card-hover-border);
    box-shadow: var(--shadow-hover);
  }

  // Focus du lien étiré : contour sur toute la carte
  &:has(.member-card__name a:focus-visible) {
    outline: 2px solid var(--saffron);
    outline-offset: 2px;
  }

  &__portrait {
    display: grid;
    place-items: center;
    aspect-ratio: 372 / 256;
    overflow: hidden;
    background: var(--ivory);
    border-bottom: 1px solid var(--border);
  }

  // Photo recadrée sur le visage (maquette : 28 % du haut), léger zoom au survol
  &__photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: 50% 28%;
    transition: transform 0.5s ease;
  }

  &:hover &__photo {
    transform: scale(1.025);
  }

  &__body {
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: 16px 16px 14px;
  }

  &__name {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.3rem;
    line-height: 1.15;
    color: var(--ink);

    a {
      color: inherit;
      text-decoration: none;

      // Carte entièrement cliquable
      &::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 1;
      }

      &:focus-visible {
        outline: none;
      }
    }
  }

  &__country {
    margin-top: 3px;
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &__expertises {
    display: grid;
    gap: 2px;
    margin: 10px 0 0;
    padding: 0;
    list-style: none;

    li {
      font-size: var(--fs-xs);
      line-height: 1.45;
      color: var(--ink-2);
    }
  }

  &__foot {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    margin-top: auto;
    padding-top: 14px;
  }

  // Marque-page au-dessus de la photo, cliquable par-dessus le lien étiré de la carte
  &__bookmark {
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 2;
    width: 32px;
    min-width: 32px;
    height: 32px;
    min-height: 32px;
    padding: 0;
    border-radius: var(--radius-xs);
    background: rgba(252, 250, 246, 0.94);
    box-shadow: 0 1px 2px rgba(24, 32, 51, 0.08);
    color: var(--ink-2);

    :deep(.q-icon) {
      font-size: 16px;
    }

    :deep(.q-focus-helper) {
      display: none;
    }

    &:hover,
    &--on {
      color: var(--terracotta);
    }
  }

  // Prochain événement : puce terracotta (maquette .m-next)
  &__next {
    font-size: var(--fs-xs);
    line-height: 1.45;
    color: var(--muted);

    b {
      display: flex;
      align-items: center;
      gap: 6px;
      font-weight: 500;
      color: var(--ink);

      &::before {
        content: '';
        flex: none;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--terracotta);
      }
    }

    a {
      position: relative;
      z-index: 2;
      color: inherit;
      text-decoration: none;

      &:hover,
      &:focus-visible {
        color: var(--link-hover);
      }
    }
  }

  &__available {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: var(--fs-xs);
    font-weight: 500;
    color: var(--sage-ink);

    &::before {
      content: '';
      flex: none;
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--sage);
    }
  }

  &__arrow {
    flex: none;
    width: 30px;
    height: 30px;
    margin: 0 -6px -4px auto;
    border-radius: 50%;
    color: var(--petrol);
    transition:
      background 0.15s,
      transform 0.15s;
  }

  &:hover &__arrow {
    background: var(--petrol-light);
    transform: translateX(2px);
  }
}

// Vue liste (ordinateur et tablette) : portrait à gauche
@media (min-width: 720px) {
  .member-grid--list .member-card {
    flex-direction: row;

    .member-card__portrait {
      flex: none;
      width: 38%;
      aspect-ratio: auto;
      border-right: 1px solid var(--border);
      border-bottom: 0;
    }
  }
}

// Petits téléphones : carte horizontale, portrait à gauche
@media (max-width: 560px) {
  .member-card,
  .member-grid--list .member-card {
    flex-direction: row;

    // Portrait à gauche : marque-page dans son coin haut gauche (maquette)
    .member-card__bookmark {
      top: 6px;
      right: auto;
      left: 6px;
      width: 28px;
      min-width: 28px;
      height: 28px;
      min-height: 28px;
    }

    .member-card__portrait {
      flex: none;
      width: 36%;
      max-width: 150px;
      aspect-ratio: auto;
      border-right: 1px solid var(--border);
      border-bottom: 0;
    }

    .member-card__body {
      min-width: 0;
      padding: 12px 12px 10px 14px;
    }

    .member-card__name {
      font-size: 1.12rem;
    }
  }
}
</style>
