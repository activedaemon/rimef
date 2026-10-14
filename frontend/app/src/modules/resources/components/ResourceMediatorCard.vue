<script setup lang="ts">
// Médiatrice liée à une ressource (maquette .pp) : portrait, nom (vers son profil), pays et
// expertise, badge de son lien (Autrice, Contributrice en terracotta ; Citée et « A recommandé
// cette ressource » en sauge), note du lien et phrase de recommandation. L'enveloppe (sauf pour
// soi-même) demande la fenêtre de message, sans quitter la fiche.
import { computed } from 'vue';

import MemberAvatar from '@/components/MemberAvatar.vue';
import { useSession } from '@/stores/session';
import type { ResourceMediator } from '../services/resources';

const props = defineProps<{ mediator: ResourceMediator }>();
const emit = defineEmits<{ write: [mediator: ResourceMediator] }>();

const session = useSession();
const isSelf = computed(() => props.mediator.id === session.user?.id);
const profile = computed(() => ({ name: 'member', params: { slug: props.mediator.slug } }));
const where = computed(() =>
  [props.mediator.country, props.mediator.expertise].filter(Boolean).join(' · ')
);
const badge = computed(() =>
  props.mediator.role
    ? { label: props.mediator.role.label, strong: props.mediator.role.value !== 'mentioned' }
    : { label: 'A recommandé cette ressource', strong: false }
);
</script>

<template>
  <article class="mediator-card">
    <div class="mediator-card__top">
      <MemberAvatar :name="mediator.name" :photo="mediator.photo_url" :size="56" />
      <div class="mediator-card__identity">
        <h3>
          <router-link :to="profile">{{ mediator.name }}</router-link>
        </h3>
        <p v-if="where" class="mediator-card__where">{{ where }}</p>
      </div>
    </div>

    <p>
      <q-badge
        class="rimef-badge"
        :class="badge.strong ? 'mediator-card__badge--strong' : 'mediator-card__badge'"
      >
        {{ badge.label }}
      </q-badge>
    </p>
    <p v-if="mediator.note" class="mediator-card__note">{{ mediator.note }}</p>
    <blockquote v-if="mediator.quote" class="mediator-card__quote">
      « {{ mediator.quote }} »
    </blockquote>

    <div class="mediator-card__foot">
      <router-link :to="profile" class="link-more">Voir le profil</router-link>
      <q-btn
        v-if="!isSelf"
        flat
        round
        dense
        :aria-label="`Écrire à ${mediator.name}`"
        aria-haspopup="dialog"
        class="mediator-card__write"
        @click="emit('write', mediator)"
      >
        <span class="mediator-card__envelope" aria-hidden="true">
          <q-icon name="mail" size="14px" />
        </span>
      </q-btn>
    </div>
  </article>
</template>

<style lang="scss" scoped>
.mediator-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 100%;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--card);
  color: var(--ink);

  &__top {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
  }

  &__identity {
    min-width: 0;
  }

  h3 {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.2rem;
    line-height: 1.1;

    a {
      color: inherit;
      text-decoration: none;

      &:hover,
      &:focus-visible {
        color: var(--petrol);
      }
    }
  }

  &__where {
    margin-top: 2px;
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &__badge.q-badge {
    background: var(--sage-light);
    color: var(--sage-ink);
  }

  &__badge--strong.q-badge {
    background: var(--terracotta-light);
    color: var(--terracotta-ink);
  }

  &__note {
    font-size: var(--fs-sm);
    line-height: 1.5;
    color: var(--ink-2);
  }

  &__quote {
    margin: 0;
    padding-left: 12px;
    border-left: 2px solid var(--terracotta-light);
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.08rem;
    font-style: italic;
    line-height: 1.35;
    color: var(--ink);
  }

  &__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid var(--border);
  }

  // Cercle de 30 px de la maquette (.pp-msg) dans une zone tactile de 44 px, invisible
  &__write.q-btn {
    width: 44px;
    height: 44px;
    margin: -7px;
    color: var(--petrol);

    :deep(.q-focus-helper) {
      display: none;
    }

    &:hover {
      background: transparent;
    }

    &:hover .mediator-card__envelope {
      border-color: transparent;
      background: var(--petrol-light);
    }
  }

  &__envelope {
    display: grid;
    place-items: center;
    width: 30px;
    height: 30px;
    border: 1px solid var(--border);
    border-radius: 50%;
  }
}
</style>
