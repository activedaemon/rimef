<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro
        title="À propos du réseau"
        lead="Le Réseau International des Médiatrices Francophones, sa mission et ses membres."
      />
      <EmptyState icon="info-circle" title="La présentation du réseau arrive bientôt" />
    </div>
  </q-page>
</template>
