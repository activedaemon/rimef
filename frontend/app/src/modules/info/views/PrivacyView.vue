<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro
        title="Politique de confidentialité"
        lead="Les données collectées par la plateforme et vos droits."
      />
      <EmptyState icon="shield-lock" title="La politique de confidentialité arrive bientôt" />
    </div>
  </q-page>
</template>
