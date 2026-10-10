<script setup lang="ts">
// Conversation de la messagerie interne : fil des messages (plus anciens en haut,
// « Messages précédents » pour remonter), zone de réponse. À l'ouverture, tout est lu.
import { isAxiosError } from 'axios';
import { computed, nextTick, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

import AppButton from '@/components/AppButton.vue';
import BackLink from '@/components/BackLink.vue';
import EmptyState from '@/components/EmptyState.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { useNotify } from '@/composables/useNotify';
import { fullMoment, shortMoment } from '@/lib/dates';
import { extractApiError } from '@/lib/http';
import { useInbox } from '@/stores/inbox';
import {
  MAX_MESSAGE_LENGTH,
  fetchConversation,
  fetchMessages,
  markConversationRead,
  sendMessage,
  type Conversation,
  type Message,
} from '../services/messages';

const route = useRoute();
const notify = useNotify();
const inbox = useInbox();

const conversation = ref<Conversation | null>(null);
/** Du plus ancien au plus récent. */
const messages = ref<Message[]>([]);
const hasMore = ref(false);
const loading = ref(false);
const loadingOlder = ref(false);
const notFound = ref(false);
const draft = ref('');
const sending = ref(false);

const id = computed(() => Number(route.params.id));
const contactName = computed(() => conversation.value?.contact?.name ?? 'Compte supprimé');
const canSend = computed(() => draft.value.trim() !== '' && !sending.value);

async function load(): Promise<void> {
  loading.value = true;
  notFound.value = false;
  conversation.value = null;
  messages.value = [];
  try {
    const [found, page] = await Promise.all([fetchConversation(id.value), fetchMessages(id.value)]);
    conversation.value = found;
    messages.value = [...page.messages].reverse();
    hasMore.value = page.hasMore;
    await scrollToEnd();
    void markConversationRead(id.value)
      .then(() => inbox.refresh(true))
      .catch(() => undefined);
  } catch (error) {
    if (isAxiosError(error) && error.response?.status === 404) {
      notFound.value = true;
    } else {
      notify.error(extractApiError(error, 'La conversation n’a pas pu être chargée. Réessayez.'));
    }
  } finally {
    loading.value = false;
  }
}

async function loadOlder(): Promise<void> {
  const oldest = messages.value[0];
  if (!oldest) return;
  loadingOlder.value = true;
  try {
    const page = await fetchMessages(id.value, oldest.id);
    messages.value = [...[...page.messages].reverse(), ...messages.value];
    hasMore.value = page.hasMore;
  } catch (error) {
    notify.error(extractApiError(error, 'Les messages précédents n’ont pas pu être chargés.'));
  } finally {
    loadingOlder.value = false;
  }
}

async function send(): Promise<void> {
  if (!canSend.value) return;
  sending.value = true;
  try {
    messages.value.push(await sendMessage(id.value, draft.value.trim()));
    draft.value = '';
    await scrollToEnd();
  } catch (error) {
    notify.error(extractApiError(error, 'Le message n’a pas pu être envoyé. Réessayez.'));
  } finally {
    sending.value = false;
  }
}

/** Dernier message et zone de réponse visibles. */
async function scrollToEnd(): Promise<void> {
  await nextTick();
  window.scrollTo({ top: document.body.scrollHeight });
}

watch(id, load, { immediate: true });
</script>

<template>
  <q-page class="inner-page">
    <div class="container thread-page">
      <BackLink label="Mes messages" :to="{ name: 'messages' }" class="thread-page__back" />

      <div v-if="loading" class="thread-page__loading">
        <q-spinner size="32px" color="primary" aria-label="Chargement de la conversation" />
      </div>

      <EmptyState
        v-else-if="notFound"
        icon="message-off"
        title="Conversation introuvable"
        text="Elle n’existe pas ou vous n’y participez pas."
      />

      <template v-else-if="conversation">
        <header class="thread-head">
          <MemberAvatar
            :name="contactName"
            :photo="conversation.contact?.photo_url ?? null"
            :size="48"
          />
          <div>
            <h1 class="thread-head__name">
              <router-link
                v-if="conversation.contact?.has_profile"
                :to="{ name: 'member', params: { slug: conversation.contact.slug } }"
              >
                {{ contactName }}
              </router-link>
              <template v-else>{{ contactName }}</template>
            </h1>
            <p class="thread-head__hint">Messagerie du réseau</p>
          </div>
        </header>

        <q-card flat class="rimef-card thread">
          <div v-if="hasMore" class="thread__older">
            <AppButton
              variant="quiet"
              size="sm"
              label="Messages précédents"
              :loading="loadingOlder"
              @click="loadOlder"
            />
          </div>

          <ol class="thread__messages" aria-label="Messages">
            <li
              v-for="message in messages"
              :key="message.id"
              class="bubble"
              :class="message.is_mine ? 'bubble--mine' : 'bubble--theirs'"
            >
              <p v-if="message.subject" class="bubble__subject">{{ message.subject }}</p>
              <p class="bubble__body">{{ message.body }}</p>
              <time
                class="bubble__time"
                :datetime="message.sent_at"
                :title="fullMoment(message.sent_at)"
              >
                <span class="sr-only">{{ message.is_mine ? 'Vous' : contactName }}, </span>
                {{ shortMoment(message.sent_at) }}
              </time>
            </li>
          </ol>

          <form v-if="conversation.contact" class="thread__reply" @submit.prevent="send">
            <q-input
              v-model="draft"
              type="textarea"
              autogrow
              outlined
              :maxlength="MAX_MESSAGE_LENGTH"
              counter
              placeholder="Votre message…"
              aria-label="Votre message"
              class="thread__input"
              @keydown.ctrl.enter.prevent="send"
              @keydown.meta.enter.prevent="send"
            />
            <AppButton
              type="submit"
              icon="send"
              label="Envoyer"
              :loading="sending"
              :disable="!canSend"
            />
          </form>
        </q-card>
      </template>
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.thread-page {
  max-width: 820px;

  &__back {
    margin-top: var(--s-4);
  }

  &__loading {
    display: grid;
    place-items: center;
    padding-block: var(--s-16);
  }
}

.thread-head {
  display: flex;
  align-items: center;
  gap: var(--s-4);
  padding-block: var(--s-4) var(--s-6);

  &__name {
    font-size: var(--fs-h2);
    line-height: 1.1;

    a {
      color: inherit;
      text-decoration: none;

      &:hover,
      &:focus-visible {
        color: var(--link-hover);
      }
    }
  }

  &__hint {
    font-size: var(--fs-xs);
    color: var(--muted);
  }
}

.thread {
  padding: var(--s-6);

  &__older {
    display: flex;
    justify-content: center;
    margin-bottom: var(--s-4);
  }

  &__messages {
    display: flex;
    flex-direction: column;
    gap: var(--s-3);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__reply {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: var(--s-3);
    align-items: start;
    margin-top: var(--s-6);
    padding-top: var(--s-5);
    border-top: 1px solid var(--border);
  }
}

// Bulles : les miennes à droite (pétrole clair), les siennes à gauche (filet)
.bubble {
  max-width: min(78%, 560px);
  padding: 10px 14px 8px;
  border-radius: var(--radius-md);

  &--mine {
    align-self: flex-end;
    background: var(--petrol-light);
    border-bottom-right-radius: var(--radius-xs);
  }

  &--theirs {
    align-self: flex-start;
    background: var(--paper);
    border: 1px solid var(--border);
    border-bottom-left-radius: var(--radius-xs);
  }

  &__subject {
    margin-bottom: 4px;
    font-size: var(--fs-xs);
    font-weight: 600;
    color: var(--terracotta-ink);
  }

  // Retours à la ligne saisis conservés
  &__body {
    color: var(--ink);
    white-space: pre-line;
    overflow-wrap: anywhere;
  }

  &__time {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    color: var(--muted);
    text-align: right;
  }
}

@media (max-width: 719px) {
  .thread {
    padding: var(--s-4);

    &__reply {
      grid-template-columns: 1fr;
    }
  }

  .bubble {
    max-width: 88%;
  }
}
</style>
