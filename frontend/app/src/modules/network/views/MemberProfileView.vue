<script setup lang="ts">
// Fiche d'une médiatrice (/reseau/aminata-diallo) : Contacter (messagerie du réseau) et favori,
// sauf sur sa propre fiche. Le menu « … » de la maquette arrivera plus tard.
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';

import AppButton from '@/components/AppButton.vue';
import BackLink from '@/components/BackLink.vue';
import EmptyState from '@/components/EmptyState.vue';
import { useSession } from '@/stores/session';
import ContactDialog from '../components/ContactDialog.vue';
import MemberProfileSheet from '../components/MemberProfileSheet.vue';
import { useMemberProfile } from '../composables/useMemberProfile';

const route = useRoute();
const session = useSession();
const { profile, loading, notFound, toggleFavorite } = useMemberProfile(() =>
  typeof route.params.slug === 'string' ? route.params.slug : null
);

const isSelf = computed(() => profile.value?.id === session.user?.id);
const contactOpen = ref(false);

const favoriteLabel = computed(() =>
  profile.value?.is_favorite ? 'Dans mes favoris' : 'Ajouter aux favoris'
);
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <BackLink label="Retour au réseau" :to="{ name: 'network' }" class="member-back" />
    </div>

    <MemberProfileSheet v-if="profile" :profile="profile">
      <template v-if="!isSelf" #actions>
        <AppButton variant="accent" icon="mail" label="Contacter" @click="contactOpen = true" />
        <AppButton
          variant="quiet"
          icon="bookmark"
          class="member-favorite"
          :class="{ 'member-favorite--on': profile.is_favorite }"
          :label="favoriteLabel"
          :aria-pressed="profile.is_favorite"
          @click="toggleFavorite"
        />
      </template>
    </MemberProfileSheet>

    <div v-else-if="loading" class="container member-loading">
      <q-spinner size="32px" color="primary" aria-label="Chargement du profil" />
    </div>

    <div v-else-if="notFound" class="container member-not-found">
      <EmptyState
        icon="user-question"
        title="Médiatrice introuvable"
        text="Ce profil n’existe pas ou n’est plus visible dans l’annuaire."
      />
    </div>

    <ContactDialog v-if="profile && !isSelf" v-model="contactOpen" :profile="profile" />
  </q-page>
</template>

<style lang="scss" scoped>
.member-back {
  margin-top: var(--s-4);
}

.member-not-found {
  margin-top: var(--s-4);
}

.member-loading {
  display: grid;
  place-items: center;
  padding-block: var(--s-16);
}

// Favori enregistré : fond terracotta clair (maquette .btn-fav[aria-pressed="true"])
.member-favorite.member-favorite--on {
  border-color: var(--terracotta-light);
  background: var(--terracotta-light);
  color: var(--terracotta-ink);
}
</style>
