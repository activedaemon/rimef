<script setup lang="ts">
// Fiche d'une médiatrice (/reseau/aminata-diallo) : Contacter (messagerie du réseau) et favori,
// sauf sur sa propre fiche. Contacter ouvre la fenêtre du premier message, puis l'onglet
// Messages une fois la conversation engagée. Menu « … » de la maquette affiché avec ses entrées
// désactivées, en attendant leur réalisation (étapes C1 : copier le lien, recommander ;
// C2 : inviter à un événement ; C3 : signaler).
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import AppButton from '@/components/AppButton.vue';
import BackLink from '@/components/BackLink.vue';
import EmptyState from '@/components/EmptyState.vue';
import { useSession } from '@/stores/session';
import ContactDialog from '../components/ContactDialog.vue';
import MemberProfileSheet from '../components/MemberProfileSheet.vue';
import { useMemberProfile } from '../composables/useMemberProfile';

const route = useRoute();
const router = useRouter();
const session = useSession();
const { profile, loading, notFound, refresh, toggleFavorite } = useMemberProfile(() =>
  typeof route.params.slug === 'string' ? route.params.slug : null
);

const isSelf = computed(() => profile.value?.id === session.user?.id);
const contactOpen = ref(false);

function showMessages(): void {
  void router.replace({ query: { ...route.query, onglet: 'messages' } });
}

function contact(): void {
  if (profile.value?.conversation) showMessages();
  else contactOpen.value = true;
}

/** Premier message envoyé : la fiche connaît désormais la conversation, l'onglet s'ouvre. */
async function onSent(): Promise<void> {
  await refresh();
  showMessages();
}

/** Entrées du menu « … » (maquette), pas encore disponibles. */
const MORE_ACTIONS = [
  { icon: 'calendar-plus', label: 'Inviter à un événement' },
  { icon: 'link', label: 'Copier le lien du profil' },
  { icon: 'id-badge-2', label: 'Recommander à une membre' },
  { icon: 'flag', label: 'Signaler une information', separated: true },
];

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
        <div class="member-actions">
          <AppButton
            variant="accent"
            icon="mail"
            label="Contacter"
            class="member-actions__contact"
            @click="contact"
          />
          <AppButton
            variant="quiet"
            icon="dots"
            aria-label="Plus d’actions"
            aria-haspopup="menu"
            class="member-actions__more"
          >
            <q-menu anchor="bottom right" self="top right" :offset="[0, 6]" class="member-menu">
              <q-list role="menu" aria-label="Plus d’actions" dense>
                <q-item
                  v-for="action in MORE_ACTIONS"
                  :key="action.label"
                  disable
                  role="menuitem"
                  aria-disabled="true"
                  :class="{ 'member-menu__separated': action.separated }"
                >
                  <q-item-section avatar><q-icon :name="action.icon" size="16px" /></q-item-section>
                  <q-item-section>{{ action.label }}</q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </AppButton>
        </div>
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

    <ContactDialog
      v-if="profile && !isSelf"
      v-model="contactOpen"
      :profile="profile"
      @sent="onSent"
    />
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

// Contacter et « … » sur une ligne (maquette .p-actions), le favori en dessous
.member-actions {
  display: flex;
  gap: 10px;

  &__contact {
    flex: 1;
  }

  // Bouton carré de la maquette (.btn-square)
  &__more.app-btn {
    flex: none;
    width: 44px;
    min-width: 44px;
    padding: 0;
  }
}

// Favori enregistré : fond terracotta clair (maquette .btn-fav[aria-pressed="true"])
.member-favorite.member-favorite--on {
  border-color: var(--terracotta-light);
  background: var(--terracotta-light);
  color: var(--terracotta-ink);
}
</style>

<style lang="scss">
// Menu « … » (téléporté hors du composant) : entrées de la maquette (.menu-pop), désactivées
.member-menu {
  min-width: 240px;
  padding: 5px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-float);

  .q-item {
    min-height: 40px;
    font-size: var(--fs-sm);
    border-radius: var(--radius-xs);
  }

  .q-item__section--avatar {
    min-width: 0;
    padding-right: 10px;
    color: var(--muted);
  }

  .member-menu__separated {
    margin-top: 5px;
    border-top: 1px solid var(--border);
  }
}
</style>
