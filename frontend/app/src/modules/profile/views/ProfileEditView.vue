<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro
        title="Modifier mon profil"
        lead="Votre parcours, vos expertises et vos coordonnées."
      />
      <EmptyState
        icon="pencil"
        title="La modification du profil arrive bientôt"
        text="Vous pourrez compléter votre parcours et vos expertises."
      />
    </div>
  </q-page>
</template>
