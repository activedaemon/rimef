<script setup lang="ts">
// Bandeau « Lues et partagées par les médiatrices » (maquette Ressources .voices) : les derniers
// avis des médiatrices, chacun avec sa phrase, la ressource concernée (vers sa fiche) et la
// médiatrice (vers son profil). Fond sable, feuilles en coin. « Partager une ressource » annonce le futur formulaire
// de proposition. Colonne de droite sur ordinateur, bandeau sous la grille sur tablette et mobile.
import AppButton from '@/components/AppButton.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { useNotify } from '@/composables/useNotify';
import { LEAF_PATH, leafTransform } from '@/lib/leaf';
import type { ResourceVoice } from '../services/resources';

defineProps<{ voices: ResourceVoice[] }>();

const notify = useNotify();

function share(): void {
  notify.info('Le partage de ressources arrive bientôt.');
}

function memberDetail(voice: ResourceVoice): string {
  return [voice.member.country, voice.member.expertise].filter(Boolean).join(' · ');
}
</script>

<template>
  <section class="resource-voices" aria-labelledby="resource-voices-title">
    <svg class="resource-voices__leaf" viewBox="0 0 130 96" aria-hidden="true">
      <path
        :d="LEAF_PATH"
        :transform="leafTransform(124, 92, -150, 96, 34)"
        fill="var(--terracotta)"
        opacity=".8"
      />
      <path
        :d="LEAF_PATH"
        :transform="leafTransform(126, 94, -112, 70, 25)"
        fill="var(--leaf-sage)"
        opacity=".8"
      />
    </svg>

    <div class="resource-voices__head">
      <p class="eyebrow">La bibliothèque du réseau</p>
      <h2 id="resource-voices-title" class="resource-voices__title">
        Lues et partagées par les médiatrices
      </h2>
    </div>

    <ul class="resource-voices__list">
      <li v-for="voice in voices" :key="voice.id">
        <q-card flat tag="figure" class="resource-voice">
          <blockquote class="resource-voice__quote">« {{ voice.quote }} »</blockquote>
          <p class="resource-voice__on">
            À propos de
            <router-link :to="{ name: 'resource', params: { slug: voice.resource.slug } }">{{
              voice.resource.title
            }}</router-link>
          </p>
          <figcaption class="resource-voice__who">
            <MemberAvatar :name="voice.member.name" :photo="voice.member.photo_url" :size="34" />
            <span>
              <router-link
                :to="{ name: 'member', params: { slug: voice.member.slug } }"
                class="resource-voice__name"
              >
                {{ voice.member.name }}
              </router-link>
              <small v-if="memberDetail(voice)">{{ memberDetail(voice) }}</small>
            </span>
          </figcaption>
        </q-card>
      </li>
    </ul>

    <AppButton
      variant="outline"
      icon="plus"
      label="Partager une ressource"
      class="resource-voices__share"
      @click="share"
    />
  </section>
</template>

<style lang="scss" scoped>
.resource-voices {
  position: relative;
  overflow: hidden;
  padding: 30px 32px 28px;
  border: 1px solid var(--sand-line);
  border-radius: var(--radius-sm);
  background: var(--sand);

  &__leaf {
    position: absolute;
    right: -6px;
    bottom: -14px;
    width: 130px;
    height: 96px;
    opacity: 0.8;
    transform: scaleY(-1);
    pointer-events: none;
  }

  &__head {
    position: relative;
  }

  &__title {
    margin-top: 8px;
    font-size: 1.9rem;
    color: var(--petrol-dark);
  }

  // Colonne de droite (ordinateur) : avis empilés, bouton sur toute la largeur
  &__list {
    position: relative;
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    margin: 22px 0 0;
    padding: 0;
    list-style: none;

    > li {
      display: flex;
    }
  }

  &__share {
    position: relative;
    width: 100%;
    margin-top: 16px;
  }
}

.resource-voice.q-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
  margin: 0;
  padding: 18px 18px 16px;
  border-radius: var(--radius-sm);
  background: var(--paper);
}

.resource-voice {
  &__quote {
    margin: 0;
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.22rem;
    font-style: italic;
    line-height: 1.3;
    color: var(--ink);
    text-wrap: pretty;
  }

  &__on {
    font-size: var(--fs-xs);
    color: var(--muted);

    a {
      font-weight: 500;
      color: var(--petrol);
      text-decoration: underline;
      text-decoration-color: rgba(35, 76, 85, 0.35);
      text-underline-offset: 3px;

      &:hover {
        color: var(--link-hover);
      }
    }
  }

  &__who {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid var(--border);
    font-size: var(--fs-sm);

    small {
      display: block;
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }

  &__name {
    display: block;
    font-weight: 600;
    color: var(--ink);
    text-decoration: none;

    &:hover {
      color: var(--petrol);
    }
  }
}

// Tablette : bandeau sous la grille, trois avis côte à côte, bouton à sa taille
@media (max-width: 1079px) {
  .resource-voices {
    margin-top: 44px;

    &__list {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    &__share {
      width: auto;
    }
  }
}

@media (max-width: 899px) {
  .resource-voices__list {
    grid-template-columns: 1fr;
  }
}

// Téléphone : bouton sur toute la largeur
@media (max-width: 719px) {
  .resource-voices {
    margin-top: 32px;
    padding: 24px 18px 22px;

    &__title {
      font-size: 1.6rem;
    }

    &__share {
      width: 100%;
    }
  }
}
</style>
