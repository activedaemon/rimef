<script setup lang="ts">
// Fiche d'une médiatrice : page provisoire, en attendant l'écran « Profil médiatrice ».
import BackLink from '@/components/BackLink.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <BackLink label="Retour à l’annuaire" :to="{ name: 'network' }" class="member-back" />
      <PageIntro title="Profil médiatrice" />
      <EmptyState
        icon="id-badge-2"
        title="La fiche arrive bientôt"
        text="Parcours, expertises et événements de la médiatrice seront affichés ici."
      />
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.member-back {
  margin-top: var(--s-6);
}
</style>
