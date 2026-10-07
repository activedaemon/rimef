<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro title="Réseau" lead="Les médiatrices et professionnelles de la paix du réseau." />
      <EmptyState
        icon="users"
        title="L’annuaire arrive bientôt"
        text="Vous pourrez rechercher une médiatrice par pays, expertise ou langue."
      />
    </div>
  </q-page>
</template>
