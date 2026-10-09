<script setup lang="ts">
// Ressources à découvrir (maquette Accueil) : la rubrique Ressources n'existe pas encore,
// le bloc l'annonce simplement.
</script>

<template>
  <q-card flat class="rimef-card home-block" tag="section" aria-labelledby="resources-title">
    <div class="card-head">
      <h2 id="resources-title" class="card-title">Ressources à découvrir</h2>
      <router-link :to="{ name: 'resources' }" class="link-more">Voir tout</router-link>
    </div>
    <p class="home-block__empty">Les ressources du réseau arrivent bientôt.</p>
  </q-card>
</template>

<style lang="scss" scoped>
@use './home-block';
</style>
