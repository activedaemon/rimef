<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro title="Agenda" lead="Les rencontres internationales et régionales du réseau." />
      <EmptyState
        icon="calendar"
        title="L’agenda arrive bientôt"
        text="Vous y trouverez les événements à venir et les membres qui y participent."
      />
    </div>
  </q-page>
</template>
