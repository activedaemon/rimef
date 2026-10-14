<script setup lang="ts">
// Colonne de la fiche ressource (maquette .fe-side) : « En bref » (type, éditeur, publication,
// langue, longueur, région) et « Dans la même thématique » (vers les fiches). Collée au
// défilement sur ordinateur ; sous le contenu sur tablette et téléphone.
import { computed } from 'vue';

import { resourceMeta, publishedLabel, type ResourceDetail } from '../services/resources';
import ResourceTypeBadge from './ResourceTypeBadge.vue';

const props = defineProps<{ resource: ResourceDetail }>();

const facts = computed(() => {
  const resource = props.resource;
  const length = [
    resource.pages ? `${resource.pages} pages` : null,
    resource.duration_minutes ? `${resource.duration_minutes} min` : null,
  ].filter(Boolean);
  return [
    { label: 'Type', value: resource.type.label },
    {
      label: resource.author ? 'Autrice' : 'Éditeur',
      value: resource.source ?? resource.author?.name,
    },
    { label: 'Publication', value: publishedLabel(resource.published_on) },
    {
      label: resource.languages.length > 1 ? 'Langues' : 'Langue',
      value: resource.languages.join(', '),
    },
    { label: 'Longueur', value: length.join(' · ') },
    { label: 'Région', value: resource.region },
  ].filter((fact): fact is { label: string; value: string } => Boolean(fact.value));
});
</script>

<template>
  <aside class="resource-side" aria-label="Informations sur la ressource">
    <q-card flat tag="section" class="rimef-card resource-side__card">
      <h2>En bref</h2>
      <dl class="resource-side__facts">
        <template v-for="fact in facts" :key="fact.label">
          <dt>{{ fact.label }}</dt>
          <dd>{{ fact.value }}</dd>
        </template>
      </dl>
    </q-card>

    <q-card
      v-if="resource.related.length"
      flat
      tag="section"
      class="rimef-card resource-side__card"
    >
      <h2>Dans la même thématique</h2>
      <ul class="resource-side__related">
        <li v-for="item in resource.related" :key="item.id">
          <router-link :to="{ name: 'resource', params: { slug: item.slug } }">
            <ResourceTypeBadge :type="item.type" />
            <b>{{ item.title }}</b>
            <small>{{ [item.source, ...resourceMeta(item)].filter(Boolean).join(' · ') }}</small>
          </router-link>
        </li>
      </ul>
    </q-card>
  </aside>
</template>

<style lang="scss" scoped>
.resource-side {
  position: sticky;
  top: calc(var(--header-h) + 20px + env(safe-area-inset-top, 0px));
  display: grid;
  gap: 20px;

  &__card {
    padding: 22px 22px 20px;

    h2 {
      margin-bottom: 12px;
      // noinspection CssNoGenericFontName
      font-family: var(--font-ui);
      font-size: var(--fs-sm);
      font-weight: 600;
      color: var(--ink);
    }
  }

  &__facts {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 8px 16px;
    margin: 0;
    font-size: var(--fs-sm);

    dt {
      color: var(--muted);
    }

    dd {
      margin: 0;
      color: var(--ink);
    }
  }

  &__related {
    display: grid;
    margin: 0;
    padding: 0;
    list-style: none;

    li + li {
      border-top: 1px solid var(--border);
    }

    a {
      display: grid;
      justify-items: start;
      gap: 6px;
      padding: 12px 0;
      color: inherit;
      text-decoration: none;

      &:hover b {
        color: var(--petrol);
      }
    }

    b {
      font-size: var(--fs-sm);
      font-weight: 600;
      line-height: 1.35;
    }

    small {
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }
}

// Tablette : sous le contenu, les deux cartes côte à côte
@media (max-width: 1079px) {
  .resource-side {
    position: static;
    grid-template-columns: 1fr 1fr;
    align-items: start;
  }
}

@media (max-width: 719px) {
  .resource-side {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
</style>
