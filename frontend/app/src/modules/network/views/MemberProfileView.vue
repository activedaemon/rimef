<script setup lang="ts">
// Fiche d'une médiatrice : page provisoire, en attendant l'écran « Profil médiatrice ».
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <q-btn
        flat
        dense
        no-caps
        icon="arrow-left"
        label="Retour à l’annuaire"
        color="primary"
        class="member-back"
        :to="{ name: 'network' }"
      />
      <PageIntro title="Profil médiatrice" />
      <EmptyState
        icon="id-badge-2"
        title="La fiche arrive bientôt"
        text="Parcours, expertises, langues et événements de la médiatrice seront affichés ici."
      />
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.member-back {
  margin-top: var(--s-6);
}
</style>
