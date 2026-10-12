<script setup lang="ts">
// Fiche d'un événement : provisoire, en attendant la maquette « Fiche événement ».
import BackLink from '@/components/BackLink.vue';
import EmptyState from '@/components/EmptyState.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <BackLink :to="{ name: 'agenda' }" label="Retour à l’agenda" />
      <EmptyState
        icon="calendar-event"
        title="La fiche de l’événement arrive bientôt"
        text="Vous y trouverez le programme, le lieu et les médiatrices qui y participent."
      />
    </div>
  </q-page>
</template>
