<script setup lang="ts">
// Mes messages : conversations de la messagerie interne, la plus récente d'abord, avec
// l'interlocutrice, l'extrait du dernier message et les non-lus.
import { onMounted, ref } from 'vue';

import AppButton from '@/components/AppButton.vue';
import EmptyState from '@/components/EmptyState.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import PageIntro from '@/components/PageIntro.vue';
import { useNotify } from '@/composables/useNotify';
import { shortMoment } from '@/lib/dates';
import { extractApiError } from '@/lib/http';
import { fetchConversations, type Conversation } from '../services/messages';

const notify = useNotify();

const conversations = ref<Conversation[]>([]);
const page = ref(0);
const lastPage = ref(1);
const loading = ref(false);
const loaded = ref(false);

async function load(next: number): Promise<void> {
  loading.value = true;
  try {
    const result = await fetchConversations(next);
    conversations.value = next === 1 ? result.data : [...conversations.value, ...result.data];
    page.value = result.meta.current_page;
    lastPage.value = result.meta.last_page;
  } catch (error) {
    notify.error(extractApiError(error, 'Vos messages n’ont pas pu être chargés. Réessayez.'));
  } finally {
    loading.value = false;
    loaded.value = true;
  }
}

onMounted(() => load(1));
</script>

<template>
  <q-page class="inner-page">
    <div class="container conversations">
      <PageIntro title="Mes messages" lead="Vos échanges avec les membres du réseau." />

      <div v-if="!loaded" class="conversations__loading">
        <q-spinner size="32px" color="primary" aria-label="Chargement des messages" />
      </div>

      <EmptyState
        v-else-if="!conversations.length"
        icon="message"
        title="Aucune conversation pour le moment"
        text="Pour écrire à une médiatrice, ouvrez sa fiche dans le Réseau et choisissez « Contacter »."
      >
        <AppButton variant="outline" label="Parcourir le réseau" :to="{ name: 'network' }" />
      </EmptyState>

      <template v-else>
        <q-card flat class="rimef-card">
          <q-list class="conversation-list">
            <q-item
              v-for="conversation in conversations"
              :key="conversation.id"
              clickable
              :to="{ name: 'conversation', params: { id: conversation.id } }"
              class="conversation-item"
              :class="{ 'conversation-item--unread': conversation.unread_count > 0 }"
            >
              <q-item-section avatar>
                <MemberAvatar
                  :name="conversation.contact?.name ?? '?'"
                  :photo="conversation.contact?.photo_url ?? null"
                  :size="48"
                />
              </q-item-section>
              <q-item-section>
                <q-item-label class="conversation-item__name">
                  {{ conversation.contact?.name ?? 'Compte supprimé' }}
                </q-item-label>
                <q-item-label v-if="conversation.last_message" caption lines="1">
                  <template v-if="conversation.last_message.is_mine">Vous : </template>
                  {{ conversation.last_message.excerpt }}
                </q-item-label>
              </q-item-section>
              <q-item-section side top class="conversation-item__side">
                <span v-if="conversation.last_message" class="conversation-item__time">
                  {{ shortMoment(conversation.last_message.sent_at) }}
                </span>
                <q-badge
                  v-if="conversation.unread_count > 0"
                  class="rimef-badge conversation-item__count"
                  :label="conversation.unread_count"
                  :aria-label="`${conversation.unread_count} non lus`"
                />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <div v-if="page < lastPage" class="conversations__more">
          <AppButton
            variant="quiet"
            label="Afficher plus"
            :loading="loading"
            @click="load(page + 1)"
          />
        </div>
      </template>
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.conversations {
  max-width: 820px;

  &__loading {
    display: grid;
    place-items: center;
    padding-block: var(--s-16);
  }

  &__more {
    display: flex;
    justify-content: center;
    margin-top: var(--s-6);
  }
}

.conversation-item {
  min-height: 76px;
  padding: var(--s-3) var(--s-5);

  & + & {
    border-top: 1px solid var(--border);
  }

  &__name {
    font-weight: 500;
    color: var(--ink);
  }

  &__side {
    gap: var(--s-2);
    align-items: flex-end;
  }

  &__time {
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &__count.q-badge {
    background: var(--terracotta);
    color: var(--paper);
  }

  &--unread &__name {
    font-weight: 600;
  }
}

@media (max-width: 719px) {
  .conversation-item {
    padding: var(--s-3) var(--s-4);
  }
}
</style>
