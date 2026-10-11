<script setup lang="ts">
// En-tête de la conversation ouverte (maquette Mes messages) : portrait, nom, badge, lieu et
// lien vers sa fiche. Sur téléphone, bouton retour vers la liste.
import { useTemplateRef } from 'vue';

import MemberAvatar from '@/components/MemberAvatar.vue';
import type { ConversationContact } from '../services/messages';

defineProps<{ contact: ConversationContact | null; showBack?: boolean }>();
const emit = defineEmits<{ back: [] }>();

// Focus sur le nom à l'ouverture de la conversation (lecteurs d'écran, clavier)
const title = useTemplateRef<HTMLElement>('title');
defineExpose({ focusTitle: () => title.value?.focus() });
</script>

<template>
  <header class="conversation-header">
    <q-btn
      v-if="showBack"
      flat
      round
      icon="arrow-left"
      aria-label="Retour aux conversations"
      class="conversation-header__back"
      @click="emit('back')"
    />
    <MemberAvatar
      :name="contact?.name ?? '?'"
      :photo="contact?.photo_url ?? null"
      :size="44"
      class="conversation-header__avatar"
    />
    <div class="conversation-header__id">
      <div class="conversation-header__name">
        <h2 ref="title" tabindex="-1">
          {{ contact?.name ?? 'Compte supprimé' }}
        </h2>
        <q-badge
          v-if="contact?.has_profile"
          class="rimef-badge conversation-header__badge"
          color="sage-light"
          text-color="sage-ink"
          label="Membre RIMeF"
        />
      </div>
      <p v-if="contact?.place" class="meta">
        <q-icon name="map-pin" aria-hidden="true" />{{ contact.place }}
      </p>
    </div>
    <router-link
      v-if="contact?.has_profile"
      :to="{ name: 'member', params: { slug: contact.slug } }"
      class="link-more conversation-header__profile"
    >
      Voir son profil
    </router-link>
  </header>
</template>

<style lang="scss" scoped>
.conversation-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 24px;
  border-bottom: 1px solid var(--border);

  &__id {
    flex: 1;
    min-width: 0;
  }

  &__name {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px 10px;

    h2 {
      font-size: 1.45rem;
      line-height: 1.15;
      outline: none;
    }
  }

  .meta {
    margin-top: 2px;
  }
}

@media (max-width: 719px) {
  .conversation-header {
    gap: 10px;
    padding: 10px 12px;

    &__name h2 {
      font-size: 1.2rem;
    }

    &__badge {
      display: none;
    }

    &__profile {
      font-size: 11.5px;
    }
  }
}
</style>
