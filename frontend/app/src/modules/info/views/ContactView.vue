<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro title="Contact" lead="Écrire à l’équipe du réseau." />
      <EmptyState icon="mail" title="Le formulaire de contact arrive bientôt" />
    </div>
  </q-page>
</template>
