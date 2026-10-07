<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro
        title="Ressources"
        lead="Rapports, analyses, publications et formations sur la médiation."
      />
      <EmptyState
        icon="book"
        title="La bibliothèque arrive bientôt"
        text="Les ressources partagées par le réseau seront rassemblées ici."
      />
    </div>
  </q-page>
</template>
