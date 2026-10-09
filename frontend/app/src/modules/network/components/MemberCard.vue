<script setup lang="ts">
// Carte médiatrice (fiche MemberCard) : portrait, nom, pays, deux expertises, langues,
// disponibilité. Toute la carte mène au profil (lien étiré sur le nom).
// En attendant les photos, le portrait est un avatar à initiales.
import { computed } from 'vue';

import InitialsAvatar from '@/components/InitialsAvatar.vue';
import type { Member } from '../services/members';

const props = defineProps<{ member: Member }>();

const MAX_EXPERTISES = 2;

const expertises = computed(() => props.member.expertises.slice(0, MAX_EXPERTISES));
const languagesLabel = computed(
  () => `Langues : ${props.member.languages.map((language) => language.name).join(', ')}`
);
</script>

<template>
  <q-card flat tag="article" class="rimef-card member-card">
    <div class="member-card__portrait">
      <InitialsAvatar :name="member.name" :size="72" />
    </div>

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

      <p v-if="member.languages.length" class="member-card__languages" :aria-label="languagesLabel">
        <template v-for="(language, index) in member.languages" :key="language.code">
          <span v-if="index" class="member-card__dot" aria-hidden="true">·</span>
          {{ language.code.toUpperCase() }}
        </template>
      </p>

      <div class="member-card__foot">
        <p v-if="member.is_available" class="member-card__available">
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
    background: var(--ivory);
    border-bottom: 1px solid var(--border);
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

  &__languages {
    margin-top: 12px;
    font-size: var(--fs-xs);
    font-weight: 500;
    letter-spacing: 0.06em;
    color: var(--ink-2);
  }

  &__dot {
    margin-inline: 4px;
    color: var(--border-hover);
  }

  &__foot {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    margin-top: auto;
    padding-top: 14px;
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
