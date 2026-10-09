<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro
        title="Mentions légales"
        lead="L’éditeur de la plateforme, la direction de la publication et l’hébergeur."
      />
      <EmptyState icon="scale" title="Les mentions légales arrivent bientôt" />
    </div>
  </q-page>
</template>
