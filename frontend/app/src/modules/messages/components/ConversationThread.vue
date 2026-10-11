<script lang="ts">
/** Brouillons par conversation, conservés pendant la visite (communs à toutes les instances). */
const drafts = new Map<number, string>();
</script>

<script setup lang="ts">
// Fil d'une conversation (maquette Mes messages) : messages groupés par jour, plus anciens en
// haut (« Messages précédents » pour remonter), zone de saisie (Ctrl + Entrée pour envoyer).
// Partagé par Mes messages (variante « panel » : occupe la colonne) et l'onglet Messages d'une
// fiche (variante « card »). Au chargement, tout est lu : la cloche et le compteur de « Mes
// messages » sont actualisés. Un message envoyé s'affiche aussitôt (« Envoi en cours… ») ;
// en cas d'échec, il reste dans le fil avec « Réessayer ». refreshNewer() ajoute en bas les
// messages reçus depuis le chargement (appelée par Mes messages, sans temps réel).
import { isAxiosError } from 'axios';
import { QCard } from 'quasar';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

import AppButton from '@/components/AppButton.vue';
import { useNotify } from '@/composables/useNotify';
import { dayLabel, fullMoment, timeOfDay } from '@/lib/dates';
import { extractApiError } from '@/lib/http';
import { useInbox } from '@/stores/inbox';
import {
  MAX_MESSAGE_LENGTH,
  fetchMessages,
  markConversationRead,
  sendMessage,
  type Message,
} from '../services/messages';

const props = withDefaults(
  defineProps<{
    conversationId: number;
    /** Nom de l'interlocutrice, lu par les lecteurs d'écran devant ses messages. */
    contactName: string;
    /** Réponse impossible si le compte de l'interlocutrice a été supprimé. */
    canReply?: boolean;
    variant?: 'panel' | 'card';
  }>(),
  { canReply: true, variant: 'card' }
);
const emit = defineEmits<{ read: []; 'not-found': []; sent: [message: Message] }>();

type ThreadMessage = Message & { state?: 'pending' | 'failed' };

const notify = useNotify();
const inbox = useInbox();
const scroller = useTemplateRef<HTMLElement>('scroller');
const input = useTemplateRef<{ focus: () => void }>('input');

/** Du plus ancien au plus récent. */
const messages = ref<ThreadMessage[]>([]);
const hasMore = ref(false);
const loading = ref(false);
const loadingOlder = ref(false);
const draft = ref('');

const canSend = computed(() => draft.value.trim() !== '');
const isMac = typeof navigator !== 'undefined' && /Mac|iP/.test(navigator.platform);

/** Messages regroupés par jour, pour les séparateurs « Aujourd'hui », « Hier »… */
const days = computed(() => {
  const groups: { key: string; label: string; messages: ThreadMessage[] }[] = [];
  for (const message of messages.value) {
    const key = new Date(message.sent_at).toDateString();
    const last = groups.at(-1);
    if (last?.key === key) last.messages.push(message);
    else groups.push({ key, label: dayLabel(message.sent_at), messages: [message] });
  }
  return groups;
});

watch(draft, (value) => drafts.set(props.conversationId, value));

async function load(): Promise<void> {
  loading.value = true;
  messages.value = [];
  draft.value = drafts.get(props.conversationId) ?? '';
  try {
    const page = await fetchMessages(props.conversationId);
    messages.value = [...page.messages].reverse();
    hasMore.value = page.hasMore;
    loading.value = false;
    await scrollToEnd();
    markRead();
  } catch (error) {
    if (isAxiosError(error) && error.response?.status === 404) {
      emit('not-found');
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
    const page = await fetchMessages(props.conversationId, oldest.id);
    // La lecture reste à la même place malgré les messages ajoutés au-dessus
    const before = scroller.value?.scrollHeight ?? 0;
    messages.value = [...[...page.messages].reverse(), ...messages.value];
    hasMore.value = page.hasMore;
    await nextTick();
    if (scroller.value) scroller.value.scrollTop += scroller.value.scrollHeight - before;
  } catch (error) {
    notify.error(extractApiError(error, 'Les messages précédents n’ont pas pu être chargés.'));
  } finally {
    loadingOlder.value = false;
  }
}

function markRead(): void {
  void markConversationRead(props.conversationId)
    .then(() => {
      emit('read');
      return inbox.refresh(true);
    })
    .catch(() => undefined);
}

/** Messages reçus depuis le chargement : ajoutés en bas, puis la conversation est lue. */
async function refreshNewer(): Promise<void> {
  if (loading.value) return;
  const known = new Set(messages.value.map((message) => message.id));
  const lastId = Math.max(0, ...messages.value.map((message) => message.id));
  try {
    const page = await fetchMessages(props.conversationId);
    const newer = page.messages
      .filter((message) => message.id > lastId && !known.has(message.id))
      .reverse();
    if (!newer.length) return;
    const element = scroller.value;
    const atBottom =
      !element || element.scrollHeight - element.scrollTop - element.clientHeight < 80;
    messages.value.push(...newer);
    if (atBottom) await scrollToEnd();
    markRead();
  } catch {
    // Nouvel essai à la prochaine actualisation
  }
}

defineExpose({ refreshNewer });

async function deliver(message: ThreadMessage): Promise<void> {
  message.state = 'pending';
  try {
    const saved = await sendMessage(props.conversationId, message.body);
    const index = messages.value.indexOf(message);
    if (index !== -1) messages.value.splice(index, 1, saved);
    emit('sent', saved);
  } catch {
    message.state = 'failed';
  }
}

async function send(): Promise<void> {
  if (!canSend.value) return;
  messages.value.push({
    id: -Date.now(),
    body: draft.value.trim(),
    sent_at: new Date().toISOString(),
    is_mine: true,
    state: 'pending',
  });
  draft.value = '';
  input.value?.focus();
  await scrollToEnd();
  await deliver(messages.value.at(-1)!);
}

/** Dernier message visible. */
async function scrollToEnd(): Promise<void> {
  await nextTick();
  if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight;
}

watch(() => props.conversationId, load, { immediate: true });
</script>

<template>
  <component
    :is="variant === 'card' ? QCard : 'div'"
    :flat="variant === 'card' ? true : undefined"
    class="thread"
    :class="[`thread--${variant}`, { 'rimef-card': variant === 'card' }]"
  >
    <div v-if="loading" class="thread__skeleton" aria-hidden="true">
      <q-skeleton type="rect" width="58%" height="74px" class="thread__skeleton-mine" />
      <q-skeleton type="rect" width="46%" height="52px" />
      <q-skeleton type="rect" width="62%" height="90px" class="thread__skeleton-mine" />
      <q-skeleton type="rect" width="38%" height="44px" />
    </div>
    <p v-if="loading" class="sr-only">Chargement des messages…</p>

    <div
      v-else
      ref="scroller"
      class="thread__scroll"
      role="log"
      :aria-label="`Messages avec ${contactName}`"
      tabindex="0"
    >
      <div v-if="hasMore" class="thread__older">
        <AppButton
          variant="quiet"
          size="sm"
          label="Messages précédents"
          :loading="loadingOlder"
          @click="loadOlder"
        />
      </div>

      <section v-for="day in days" :key="day.key" class="thread__day">
        <h3 class="thread__day-label">{{ day.label }}</h3>
        <ol class="thread__messages">
          <li
            v-for="message in day.messages"
            :key="message.id"
            class="message"
            :class="[
              message.is_mine ? 'message--mine' : 'message--theirs',
              message.state && `message--${message.state}`,
            ]"
          >
            <span class="sr-only">{{ message.is_mine ? 'Vous' : contactName }} :</span>
            <div class="message__bubble">
              <p class="message__body">{{ message.body }}</p>
            </div>
            <p class="message__foot">
              <template v-if="message.state === 'pending'">
                <q-icon name="clock" size="13px" />Envoi en cours…
              </template>
              <template v-else-if="message.state === 'failed'">
                <q-icon name="alert-circle" size="13px" />Échec de l’envoi ·
                <q-btn
                  flat
                  dense
                  no-caps
                  :ripple="false"
                  label="Réessayer"
                  class="message__retry"
                  @click="deliver(message)"
                />
              </template>
              <time v-else :datetime="message.sent_at" :title="fullMoment(message.sent_at)">
                {{ timeOfDay(message.sent_at) }}
              </time>
            </p>
          </li>
        </ol>
      </section>
    </div>

    <form v-if="canReply && !loading" class="thread__compose" @submit.prevent="send">
      <q-input
        ref="input"
        v-model="draft"
        type="textarea"
        autogrow
        outlined
        dense
        rows="1"
        :maxlength="MAX_MESSAGE_LENGTH"
        :counter="draft.length > MAX_MESSAGE_LENGTH - 200"
        placeholder="Écrivez votre message…"
        :aria-label="`Votre message à ${contactName}`"
        aria-describedby="thread-hint"
        class="thread__input"
        @keydown.ctrl.enter.prevent="send"
        @keydown.meta.enter.prevent="send"
      />
      <AppButton type="submit" label="Envoyer" :disable="!canSend" class="thread__send" />
      <p id="thread-hint" class="thread__hint">
        Entrée pour aller à la ligne · <kbd>{{ isMac ? '⌘' : 'Ctrl' }}</kbd> +
        <kbd>Entrée</kbd> pour envoyer
      </p>
    </form>
  </component>
</template>

<style lang="scss" scoped>
.thread {
  display: flex;
  flex-direction: column;
  min-height: 0;

  // Mes messages : occupe toute la colonne, seul le fil défile
  &--panel {
    flex: 1;
  }

  // Onglet d'une fiche : fil limité en hauteur, défilement interne
  &--card {
    .thread__scroll {
      max-height: min(60vh, 560px);
    }
  }

  &__skeleton {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 14px;
    padding: 24px;
  }

  &__skeleton-mine {
    align-self: flex-end;
  }

  &__scroll {
    flex: 1;
    min-height: 0;
    padding: 4px 24px 20px;
    overflow-y: auto;
    overscroll-behavior: contain;

    &:focus-visible {
      outline: 2px solid var(--petrol);
      outline-offset: -2px;
    }
  }

  &__older {
    display: flex;
    justify-content: center;
    padding-top: 16px;
  }

  // Séparateur de jour : petit libellé centré (maquette .ms-day)
  &__day-label {
    margin: 18px 0 10px;
    font-family: var(--font-ui);
    font-size: var(--fs-xs);
    font-weight: 500;
    line-height: 1.4;
    color: var(--muted);
    text-align: center;
  }

  &__messages {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__compose {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 6px 10px;
    align-items: end;
    padding: 14px 24px 12px;
    border-top: 1px solid var(--border);
  }

  &__input :deep(textarea) {
    max-height: 168px;
    line-height: 1.5;
    overflow-y: auto;
  }

  &__hint {
    grid-column: 1 / -1;
    font-size: 11.5px;
    color: var(--muted);

    kbd {
      padding: 0 4px;
      font-family: inherit;
      font-size: 11px;
      background: var(--ivory);
      border: 1px solid var(--border);
      border-radius: 3px;
    }
  }
}

// Bulles : les miennes à droite (pétrole clair), les siennes à gauche (filet)
.message {
  display: flex;
  flex-direction: column;
  max-width: min(76%, 560px);

  &--mine {
    align-self: flex-end;
    align-items: flex-end;
  }

  &--theirs {
    align-self: flex-start;
    align-items: flex-start;
  }

  &__bubble {
    padding: 12px 16px 13px;
    border-radius: var(--radius-md);

    .message--mine & {
      background: var(--petrol-light);
      border-bottom-right-radius: var(--radius-xs);
    }

    .message--theirs & {
      background: var(--paper);
      border: 1px solid var(--border);
      border-bottom-left-radius: var(--radius-xs);
    }

    .message--pending & {
      opacity: 0.7;
    }

    .message--failed & {
      border: 1px solid var(--terracotta-ink);
    }
  }

  // Retours à la ligne saisis conservés
  &__body {
    color: var(--ink);
    white-space: pre-line;
    overflow-wrap: anywhere;
  }

  &__foot {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
    font-size: 11px;
    color: var(--muted);
    font-variant-numeric: tabular-nums;

    .message--failed & {
      color: var(--terracotta-ink);
    }
  }

  &__retry {
    min-height: 0;
    padding: 0 2px;
    font-size: 11px;
    font-weight: 600;
    color: var(--terracotta-ink);
    text-decoration: underline;
  }
}

@media (max-width: 1080px) {
  .message {
    max-width: 82%;
  }
}

@media (max-width: 719px) {
  .thread {
    &__scroll {
      padding: 4px 16px 18px;
    }

    &__compose {
      padding: 10px 12px calc(10px + env(safe-area-inset-bottom, 0px));
    }

    &__hint {
      display: none;
    }
  }

  .message {
    max-width: 88%;

    &__bubble {
      padding: 11px 14px 12px;
      font-size: var(--fs-sm);
    }
  }
}
</style>
