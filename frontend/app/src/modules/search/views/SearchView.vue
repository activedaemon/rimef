<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';

import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
import { isSearchScope, SEARCH_SCOPES } from '@/lib/search';

const route = useRoute();
const terms = computed(() => (typeof route.query.q === 'string' ? route.query.q.trim() : ''));
const scopeLabel = computed(() =>
  isSearchScope(route.query.rubrique) ? SEARCH_SCOPES[route.query.rubrique] : null
);
const lead = computed(() => {
  if (!terms.value) {
    return 'Saisissez un nom, un événement ou un thème.';
  }
  const where = scopeLabel.value ? ` dans ${scopeLabel.value}` : '';
  return `Résultats pour « ${terms.value} »${where}`;
});
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro title="Recherche" :lead="lead" />
      <EmptyState
        icon="search"
        title="La recherche arrive bientôt"
        text="Vous pourrez retrouver ici les membres, les événements et les ressources du réseau."
      />
    </div>
  </q-page>
</template>
