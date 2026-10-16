<script setup lang="ts">
// En-tête de la conversation ouverte (maquette Mes messages) : portrait, nom, badges, lieu et
// lien vers sa fiche, menu « … » (Supprimer la conversation). Sur téléphone, bouton retour.
// Équipe RIMeF : badge pétrole et rôle (une administratrice médiatrice a aussi « Membre RIMeF »).
// Compte désactivé : badge « Compte désactivé » ; compte supprimé : portrait neutre.
import { useTemplateRef } from 'vue';

import MemberAvatar from '@/components/MemberAvatar.vue';
import { teamRoleLabel, type ConversationContact } from '../services/messages';
import AppTooltip from '@/components/AppTooltip.vue';

defineProps<{ contact: ConversationContact | null; showBack?: boolean }>();
const emit = defineEmits<{ back: []; delete: [] }>();

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
      v-if="contact"
      :name="contact.name"
      :photo="contact.photo_url"
      :size="44"
      class="conversation-header__avatar"
    />
    <q-avatar
      v-else
      size="44px"
      icon="user"
      class="conversation-header__ghost"
      aria-hidden="true"
    />
    <div class="conversation-header__id">
      <div class="conversation-header__name">
        <h2 ref="title" tabindex="-1">
          {{ contact?.name ?? 'Compte supprimé' }}
        </h2>
        <template v-if="contact?.is_team">
          <q-badge class="rimef-badge conversation-header__team" label="Équipe RIMeF" />
          <span class="conversation-header__role">{{ teamRoleLabel(contact.roles) }}</span>
        </template>
        <q-badge
          v-if="contact?.has_profile"
          class="rimef-badge conversation-header__badge"
          color="sage-light"
          text-color="sage-ink"
          label="Membre RIMeF"
        />
        <q-badge
          v-if="contact && !contact.is_active"
          outline
          class="rimef-badge conversation-header__off"
          label="Compte désactivé"
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
    <q-btn
      flat
      round
      dense
      icon="dots"
      aria-label="Options de la conversation"
      class="conversation-header__options"
    >
      <AppTooltip>Options de la conversation</AppTooltip>
      <q-menu anchor="bottom right" self="top right" class="message-menu">
        <q-list dense>
          <q-item v-close-popup clickable @click="emit('delete')">
            <q-item-section avatar><q-icon name="trash" size="16px" /></q-item-section>
            <q-item-section>Supprimer la conversation</q-item-section>
          </q-item>
        </q-list>
      </q-menu>
    </q-btn>
  </header>
</template>

<style lang="scss" scoped>
.conversation-header__team.q-badge {
  background: var(--petrol-light);
  color: var(--petrol);
}

.conversation-header__role {
  font-size: var(--fs-xs);
  font-weight: 500;
  color: var(--petrol);
}

.conversation-header__off.q-badge {
  border-color: var(--border-hover);
  color: var(--ink-2);
}

.conversation-header__ghost {
  flex: none;
  background: var(--ivory);
  color: var(--muted);
  box-shadow: inset 0 0 0 1px var(--border);
}

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

  &__options {
    min-width: 44px;
    min-height: 44px;
    margin-right: -10px;
    color: var(--ink-2);
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
