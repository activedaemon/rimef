<script setup lang="ts">
// Carte de la bibliothèque (fiche ResourceCard) : type et signet, titre, source ou autrice
// médiatrice, date et durée, deux thématiques, appel à l'action. Toute la carte mène à la fiche
// de la ressource (lien étiré sur le titre).
import { computed } from 'vue';

import MemberAvatar from '@/components/MemberAvatar.vue';
import { resourceMeta, themeTone, type LibraryResource } from '../services/resources';
import ResourceTypeBadge from './ResourceTypeBadge.vue';

const props = defineProps<{ resource: LibraryResource }>();
const emit = defineEmits<{ toggleSaved: [] }>();

// Signet plein (enregistré) : tracé SVG de l'icône Tabler « bookmark », rempli par Quasar
// (comme le marque-page de MemberCard)
const BOOKMARK_FILLED = 'M18 7v14l-6 -4l-6 4v-14a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4z|0 0 24 24';

const MAX_THEMES = 2;

const themes = computed(() => props.resource.themes.slice(0, MAX_THEMES));

const meta = computed(() => resourceMeta(props.resource).join(' · '));
const to = computed(() => ({ name: 'resource', params: { slug: props.resource.slug } }));

const saveLabel = computed(() =>
  props.resource.is_saved ? 'Retirer des ressources enregistrées' : 'Enregistrer cette ressource'
);
</script>

<template>
  <q-card flat tag="article" class="rimef-card resource-card">
    <div class="resource-card__top">
      <ResourceTypeBadge :type="resource.type" />
      <q-btn
        flat
        dense
        :ripple="false"
        class="resource-card__save"
        :class="{ 'resource-card__save--on': resource.is_saved }"
        :icon="resource.is_saved ? BOOKMARK_FILLED : 'bookmark'"
        :aria-pressed="resource.is_saved"
        :aria-label="saveLabel"
        @click="emit('toggleSaved')"
      />
    </div>

    <h3 class="resource-card__title">
      <router-link :to="to">{{ resource.title }}</router-link>
    </h3>

    <p v-if="resource.author" class="resource-card__source">
      <MemberAvatar :name="resource.author.name" :photo="resource.author.photo_url" :size="20" />
      <span class="resource-card__author">{{ resource.author.name }}</span>
      <span>· Médiatrice RIMeF</span>
    </p>
    <p v-else-if="resource.source" class="resource-card__source">{{ resource.source }}</p>

    <p class="resource-card__date">{{ meta }}</p>

    <ul v-if="themes.length" class="resource-card__themes" aria-label="Thématiques">
      <li v-for="theme in themes" :key="theme.id">
        <q-chip
          :ripple="false"
          :class="['resource-card__theme', `resource-card__theme--${themeTone(theme.id)}`]"
        >
          {{ theme.name }}
        </q-chip>
      </li>
    </ul>

    <div class="resource-card__foot" aria-hidden="true">
      <span
        class="resource-card__cta"
        :class="{ 'resource-card__cta--listen': resource.type.value === 'podcast' }"
      >
        {{ resource.type.action }}
        <q-icon name="arrow-right" size="13px" />
      </span>
      <q-icon name="arrow-right" size="18px" class="resource-card__go" />
    </div>
  </q-card>
</template>

<style lang="scss" scoped>
.resource-card {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  padding: 16px 18px 14px;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;

  &:hover {
    border-color: var(--card-hover-border);
    box-shadow: var(--shadow-hover);
  }

  // Focus du lien étiré : contour sur toute la carte
  &:has(.resource-card__title a:focus-visible) {
    outline: 2px solid var(--saffron);
    outline-offset: 2px;
  }

  &__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }

  // Signet cliquable par-dessus le lien étiré de la carte
  &__save {
    position: relative;
    z-index: 2;
    width: 44px;
    min-width: 44px;
    height: 44px;
    margin: -10px -14px -10px 0;
    padding: 0;
    border-radius: var(--radius-xs);
    color: var(--ink-2);

    :deep(.q-icon) {
      font-size: 17px;
    }

    :deep(.q-focus-helper) {
      display: none;
    }

    &:hover {
      color: var(--ink);
    }

    &--on,
    &--on:hover {
      color: var(--petrol);
    }
  }

  &__title {
    margin-top: 14px;
    font-family: var(--font-ui);
    font-size: 0.9875rem;
    font-weight: 600;
    line-height: 1.38;
    color: var(--ink);
    text-wrap: pretty;

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

  &:hover &__title a {
    color: var(--petrol-dark);
  }

  // Autrice : le nom et la mention passent à la ligne sans se couper en colonne étroite
  &__source {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 2px 7px;
    margin-top: 8px;
    font-size: var(--fs-sm);
    color: var(--muted);
  }

  &__author {
    color: var(--ink-2);
  }

  &__source > span {
    white-space: nowrap;
  }

  &__date {
    margin-top: 2px;
    font-size: var(--fs-sm);
    color: var(--muted);
    font-variant-numeric: tabular-nums;
  }

  &__themes {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 16px 0 0;
    padding: 0;
    list-style: none;
  }

  &__theme.q-chip {
    height: 24px;
    margin: 0;
    padding: 0 10px;
    border-radius: var(--radius-pill);
    font-size: 11.5px;
    font-weight: 500;

    &--saffron {
      background: var(--saffron-light);
      color: var(--saffron-ink);
    }

    &--petrol {
      background: var(--petrol-light);
      color: var(--petrol);
    }

    &--sage {
      background: var(--sage-light);
      color: var(--sage-ink);
    }

    &--terra {
      background: var(--terracotta-light);
      color: var(--terracotta-ink);
    }
  }

  &__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding-top: 18px;
  }

  &__cta {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: var(--fs-sm);
    font-weight: 600;
    color: var(--petrol-dark);

    &--listen {
      color: var(--terracotta-ink);
    }
  }

  &__go {
    color: var(--petrol-dark);
    transition: transform 0.2s;
  }

  &:hover &__go {
    transform: translateX(3px);
  }
}
</style>
