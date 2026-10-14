<script setup lang="ts">
// Fiche d'une ressource (/ressources/women-mediators-and-inclusive-peace-processes, maquette
// Fiche ressource) : en-tête et actions, « Autour de cette ressource », résumé et auteur(s),
// médiatrices liées, événement associé, colonne « En bref » et « Dans la même thématique ».
// Sur téléphone, les actions passent dans une barre fixée en bas. L'enveloppe d'une médiatrice
// ouvre la fenêtre de message sur place, sans quitter la fiche.
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';

import BackLink from '@/components/BackLink.vue';
import EmptyState from '@/components/EmptyState.vue';
import { mediatorNoun } from '@/lib/wording';
import ContactDialog from '@modules/network/components/ContactDialog.vue';
import ResourceActions from '../components/ResourceActions.vue';
import ResourceEventLink from '../components/ResourceEventLink.vue';
import ResourceHeader from '../components/ResourceHeader.vue';
import ResourceLinks from '../components/ResourceLinks.vue';
import ResourceMediatorCard from '../components/ResourceMediatorCard.vue';
import ResourceSide from '../components/ResourceSide.vue';
import { useResourcePage } from '../composables/useResourcePage';
import type { ResourceMediator } from '../services/resources';

const route = useRoute();
const { resource, loading, notFound, toggleFavorite } = useResourcePage(() =>
  typeof route.params.slug === 'string' ? route.params.slug : null
);

const paragraphs = computed(() =>
  (resource.value?.summary ?? '')
    .split(/\n\s*\n/)
    .map((text) => text.trim())
    .filter(Boolean)
);

const mediatorsTitle = computed(() => {
  const count = resource.value?.mediators.length ?? 0;
  return `${count} ${mediatorNoun(count)}`;
});

/** Sections de la fiche, remontées en vue depuis l'en-tête ou « Autour de cette ressource ». */
function goTo(section: string): void {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document
    .getElementById(section)
    ?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
}

/** Médiatrice à qui écrire (fenêtre de message ouverte tant qu'elle est choisie). */
const recipient = ref<ResourceMediator | null>(null);
const contactOpen = ref(false);

function write(mediator: ResourceMediator): void {
  recipient.value = mediator;
  contactOpen.value = true;
}
</script>

<template>
  <q-page class="inner-page resource-page" :class="{ 'resource-page--docked': resource }">
    <div class="container">
      <BackLink
        :to="{ name: 'resources' }"
        label="Retour aux ressources"
        class="resource-page__back"
      />
    </div>

    <template v-if="resource">
      <div class="container">
        <ResourceHeader :resource="resource">
          <template #actions>
            <ResourceActions :resource="resource" @toggle-favorite="toggleFavorite" />
          </template>
        </ResourceHeader>
      </div>

      <div class="container resource-body">
        <div class="resource-body__main">
          <ResourceLinks
            v-if="resource.mediators.length || resource.event"
            id="autour"
            :resource="resource"
            class="resource-page__section"
            @go="goTo"
          />

          <q-card
            v-if="paragraphs.length || resource.organization"
            id="resume"
            flat
            tag="article"
            class="rimef-card resource-card-block resource-page__section"
          >
            <section v-if="paragraphs.length" class="resource-card-block__section">
              <h2>Résumé</h2>
              <p v-for="(text, index) in paragraphs" :key="index">{{ text }}</p>
            </section>

            <section v-if="resource.organization" class="resource-card-block__section">
              <h2>Auteur(s)</h2>
              <div class="resource-author">
                <span class="resource-author__mark" aria-hidden="true">
                  <q-icon name="world" size="22px" />
                </span>
                <div>
                  <b>{{ resource.organization.name }}</b>
                  <p v-if="resource.organization.detail">{{ resource.organization.detail }}</p>
                  <a
                    v-if="resource.organization.url"
                    :href="resource.organization.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="link-more"
                  >
                    Voir le site de l’organisation<span class="sr-only"> (nouvel onglet)</span>
                  </a>
                </div>
              </div>
            </section>
          </q-card>

          <q-card
            v-if="resource.mediators.length"
            id="mediatrices"
            flat
            tag="article"
            class="rimef-card resource-card-block resource-page__section"
            aria-labelledby="resource-mediators-title"
          >
            <div class="resource-card-block__head">
              <h2 id="resource-mediators-title">Médiatrices RIMeF liées à cette ressource</h2>
              <p>{{ mediatorsTitle }}</p>
            </div>
            <ul class="resource-mediators">
              <li v-for="person in resource.mediators" :key="person.id">
                <ResourceMediatorCard :mediator="person" @write="write" />
              </li>
            </ul>
          </q-card>

          <q-card
            v-if="resource.event"
            id="evenement"
            flat
            tag="article"
            class="rimef-card resource-card-block resource-page__section"
            aria-labelledby="resource-event-title"
          >
            <div class="resource-card-block__head">
              <h2 id="resource-event-title">Événement associé</h2>
            </div>
            <ResourceEventLink :event="resource.event" />
          </q-card>

          <EmptyState
            v-if="
              !paragraphs.length &&
              !resource.organization &&
              !resource.mediators.length &&
              !resource.event
            "
            icon="book"
            title="La présentation de la ressource arrive bientôt"
            :text="
              resource.url
                ? 'En attendant, vous pouvez la consulter sur le site de son éditeur.'
                : undefined
            "
          />
        </div>

        <ResourceSide :resource="resource" />
      </div>

      <ContactDialog v-if="recipient" v-model="contactOpen" :profile="recipient" />

      <q-page-sticky v-if="$q.screen.lt.sm" position="bottom" expand class="resource-dock">
        <ResourceActions :resource="resource" dock @toggle-favorite="toggleFavorite" />
      </q-page-sticky>
    </template>

    <div v-else-if="loading" class="container resource-page__loading">
      <q-spinner size="32px" color="primary" aria-label="Chargement de la ressource" />
    </div>

    <div v-else-if="notFound" class="container resource-page__not-found">
      <EmptyState
        icon="book-off"
        title="Ressource introuvable"
        text="Cette ressource n’existe pas ou n’est plus dans la bibliothèque."
      />
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.resource-page {
  &__back {
    margin-top: var(--s-4);
  }

  &__loading {
    display: grid;
    place-items: center;
    padding-block: var(--s-16);
  }

  &__not-found {
    margin-top: var(--s-4);
  }

  // Arrivée depuis un lien de la fiche : la section ne passe pas sous l'en-tête fixe
  &__section {
    scroll-margin-top: calc(var(--header-h) + 16px);
  }
}

.resource-body {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 330px;
  gap: 24px;
  align-items: start;
  padding-block: 4px 64px;

  &__main {
    display: grid;
    gap: 20px;
    min-width: 0;
  }
}

.resource-card-block {
  padding: 28px 32px 30px;

  &__section {
    h2 {
      margin-bottom: 12px;
      font-size: 1.45rem;
    }

    p {
      max-width: 68ch;
      line-height: 1.75;
      color: var(--ink-2);
    }

    p + p {
      margin-top: 12px;
    }

    & + & {
      margin-top: 26px;
      padding-top: 24px;
      border-top: 1px solid var(--border);
    }
  }

  &__head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;

    h2 {
      font-size: 1.45rem;
    }

    p {
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }
}

.resource-author {
  display: flex;
  align-items: flex-start;
  gap: 14px;

  b {
    display: block;
    font-weight: 600;
  }

  p {
    margin-top: 2px;
    font-size: var(--fs-sm);
    line-height: 1.5;
  }

  .link-more {
    display: inline-block;
    margin-top: 8px;
    font-size: var(--fs-sm);
  }

  &__mark {
    display: grid;
    flex: none;
    place-items: center;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--petrol-light);
    color: var(--petrol);
  }
}

.resource-mediators {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin: 0;
  padding: 0;
  list-style: none;
}

// Barre d'actions du téléphone
.resource-dock {
  z-index: 45;

  :deep(> div) {
    width: 100%;
  }

  .resource-actions {
    width: 100%;
    padding: 10px var(--gutter);
    border-top: 1px solid var(--border);
    background: rgba(252, 250, 246, 0.97);
  }
}

// Tablette : colonne sous le contenu
@media (max-width: 1079px) {
  .resource-body {
    grid-template-columns: 1fr;
  }
}

// Téléphone : place pour la barre d'actions
@media (max-width: 719px) {
  .resource-page--docked {
    padding-bottom: 64px;
  }

  .resource-body {
    gap: 16px;
    padding-block: 0 40px;
  }

  .resource-card-block {
    padding: 22px 18px 24px;
  }

  .resource-mediators {
    grid-template-columns: 1fr;
  }
}
</style>
