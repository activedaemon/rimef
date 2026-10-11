<script setup lang="ts">
// Mes messages (maquette) : conversations à gauche, conversation ouverte à droite
// (/messages/:id). Le champ du bandeau filtre la liste (?q=, meta.searchInPage).
// Sur téléphone : la liste, puis la conversation seule en plein écran sous l'en-tête
// (barre d'onglets masquée, meta.fullscreenOnPhone), avec un retour vers la liste.
// Sans temps réel : tant que la page est affichée, les non-lus sont actualisés toutes les
// 20 secondes environ ; quand ils changent, la liste est rechargée discrètement et le fil ouvert
// reçoit ses nouveaux messages.
import { isAxiosError } from 'axios';
import { useQuasar } from 'quasar';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import AppButton from '@/components/AppButton.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
import { useInbox } from '@/stores/inbox';
import ConversationHeader from '../components/ConversationHeader.vue';
import ConversationList from '../components/ConversationList.vue';
import ConversationThread from '../components/ConversationThread.vue';
import NewMessageDialog from '../components/NewMessageDialog.vue';
import {
  fetchConversation,
  fetchConversations,
  type Conversation,
  type ConversationFilter,
  type Message,
} from '../services/messages';

const $q = useQuasar();
const route = useRoute();
const router = useRouter();
const inbox = useInbox();
const header = useTemplateRef<{ focusTitle: () => void }>('header');
const thread = useTemplateRef<{ refreshNewer: () => Promise<void> }>('thread');

/**
 * Actualisation des non-lus pendant que la page est affichée : juste au-delà de la limite du
 * store (20 s), pour qu'aucun passage ne soit ignoré après une actualisation récente.
 */
const REFRESH_INTERVAL_MS = 21_000;

const conversations = ref<Conversation[]>([]);
const page = ref(0);
const lastPage = ref(1);
const total = ref(0);
const unreadTotal = ref(0);
const loading = ref(false);
const failed = ref(false);
const filter = ref<ConversationFilter>('all');
const newMessageOpen = ref(false);
let controller: AbortController | null = null;

const search = computed(() => (typeof route.query.q === 'string' ? route.query.q.trim() : ''));
const activeId = computed(() => {
  const id = Number(route.params.id);
  return Number.isInteger(id) && id > 0 ? id : null;
});
const isOpenOnPhone = computed(() => $q.screen.lt.sm && activeId.value !== null);

/** Conversation ouverte absente de la liste chargée (filtre, recherche, page suivante). */
const fallback = ref<Conversation | null>(null);
const notFound = ref(false);
const active = computed(
  () =>
    conversations.value.find((conversation) => conversation.id === activeId.value) ??
    (fallback.value?.id === activeId.value ? fallback.value : null)
);

async function load(next = 1): Promise<void> {
  controller?.abort();
  const current = (controller = new AbortController());
  loading.value = true;
  failed.value = false;
  try {
    const result = await fetchConversations(
      next,
      { q: search.value, unread: filter.value === 'unread' },
      current.signal
    );
    conversations.value = next === 1 ? result.data : [...conversations.value, ...result.data];
    page.value = result.meta.current_page;
    lastPage.value = result.meta.last_page;
    total.value = result.meta.total_conversations;
    unreadTotal.value = result.meta.unread_conversations;
  } catch {
    if (current.signal.aborted) return;
    failed.value = true;
  } finally {
    if (controller === current) loading.value = false;
  }
}

/**
 * Rechargement discret après l'arrivée de messages : première page remplacée (extraits,
 * non-lus, ordre), conversations des pages suivantes conservées, sans silhouettes.
 */
async function refreshList(): Promise<void> {
  if (loading.value || failed.value) return;
  try {
    const result = await fetchConversations(1, {
      q: search.value,
      unread: filter.value === 'unread',
    });
    const fresh = new Set(result.data.map((conversation) => conversation.id));
    conversations.value = [
      ...result.data,
      ...conversations.value.filter((conversation) => !fresh.has(conversation.id)),
    ];
    lastPage.value = Math.max(lastPage.value, result.meta.last_page);
    total.value = result.meta.total_conversations;
    unreadTotal.value = result.meta.unread_conversations;
    if (active.value && active.value.unread_count > 0) await thread.value?.refreshNewer();
  } catch {
    // Actualisation indicative : la liste affichée reste valable
  }
}

async function loadMore(done: (stop?: boolean) => void): Promise<void> {
  if (page.value >= lastPage.value) return done(true);
  await load(page.value + 1);
  done(page.value >= lastPage.value);
}

/** Conversation ouverte par son adresse sans figurer dans la liste : chargée à part. */
async function ensureActive(): Promise<void> {
  notFound.value = false;
  const id = activeId.value;
  if (id === null || active.value) return;
  try {
    fallback.value = await fetchConversation(id);
  } catch (error) {
    if (isAxiosError(error) && error.response?.status === 404) notFound.value = true;
  }
}

function linkTo(conversation: Conversation) {
  return { name: 'conversation', params: { id: conversation.id }, query: route.query };
}

function openConversation(id: number): void {
  void router.push({ name: 'conversation', params: { id } });
}

/** Retour à la liste : on revient en arrière si l'on vient de la liste, pour ne pas empiler. */
function back(): void {
  const previous = (window.history.state as { back?: string } | null)?.back;
  if (previous?.startsWith('/messages') && !/\/messages\/\d+/.test(previous)) {
    router.back();
    return;
  }
  void router.push({ name: 'messages', query: route.query });
}

function clearSearch(): void {
  const query = { ...route.query };
  delete query.q;
  void router.replace({ query });
}

function onRead(): void {
  const conversation = active.value;
  if (conversation && conversation.unread_count > 0) {
    conversation.unread_count = 0;
    unreadTotal.value = Math.max(0, unreadTotal.value - 1);
  }
}

/** Réponse envoyée : la conversation passe en tête avec son nouvel extrait. */
function onSent(message: Message): void {
  const conversation = active.value;
  if (!conversation) return;
  conversation.last_message = {
    excerpt: message.body.replace(/\s+/g, ' ').slice(0, 120),
    sent_at: message.sent_at,
    is_mine: true,
  };
  const index = conversations.value.indexOf(conversation);
  if (index > 0) {
    conversations.value.splice(index, 1);
    conversations.value.unshift(conversation);
  }
}

/** Depuis « Nouveau message » : liste actualisée, conversation ouverte. */
async function onNewMessage(id: number): Promise<void> {
  if (search.value || filter.value !== 'all') {
    filter.value = 'all';
    await router.push({ name: 'conversation', params: { id } });
  } else {
    openConversation(id);
    await load();
  }
}

watch([search, filter], () => load(), { immediate: true });
watch(
  () => inbox.unreadMessages,
  () => refreshList()
);

let timer: ReturnType<typeof setInterval> | undefined;
onMounted(() => {
  timer = setInterval(() => {
    if (document.visibilityState === 'visible') void inbox.refresh();
  }, REFRESH_INTERVAL_MS);
});
onBeforeUnmount(() => clearInterval(timer));
watch([activeId, () => conversations.value.length], ensureActive, { immediate: true });
// Nouvelle conversation ouverte : focus sur le nom (téléphone : vue plein écran)
watch(activeId, async (id, previous) => {
  if (id === null || id === previous) return;
  await nextTick();
  if ($q.screen.lt.sm) window.scrollTo(0, 0);
  header.value?.focusTitle();
});
</script>

<template>
  <q-page class="inner-page messages-page" :class="{ 'messages-page--open': isOpenOnPhone }">
    <div class="container">
      <PageIntro
        v-show="!isOpenOnPhone"
        title="Mes messages"
        lead="Vos échanges avec les membres du réseau."
      />

      <q-card flat class="rimef-card inbox">
        <ConversationList
          v-show="!isOpenOnPhone"
          v-model:filter="filter"
          class="inbox__side"
          :conversations="conversations"
          :active-id="activeId"
          :link-to="linkTo"
          :loading="loading"
          :failed="failed"
          :has-more="page < lastPage"
          :search="search"
          :total="total"
          :unread-total="unreadTotal"
          @load-more="loadMore"
          @new="newMessageOpen = true"
          @retry="load()"
          @clear-search="clearSearch"
        />

        <section
          v-if="!$q.screen.lt.sm || isOpenOnPhone"
          class="inbox__conversation"
          aria-label="Conversation"
        >
          <!-- Conversation ouverte -->
          <template v-if="active">
            <ConversationHeader
              ref="header"
              :contact="active.contact"
              :show-back="$q.screen.lt.sm"
              @back="back"
            />
            <ConversationThread
              ref="thread"
              :key="active.id"
              variant="panel"
              :conversation-id="active.id"
              :contact-name="active.contact?.name ?? 'Compte supprimé'"
              :can-reply="active.contact !== null"
              @read="onRead"
              @sent="onSent"
              @not-found="notFound = true"
            />
          </template>

          <div v-else-if="notFound" class="inbox__state">
            <EmptyState
              icon="message-off"
              title="Conversation introuvable"
              text="Elle n’existe pas ou vous n’y participez pas."
            >
              <AppButton
                v-if="$q.screen.lt.sm"
                variant="outline"
                label="Retour aux conversations"
                @click="back"
              />
            </EmptyState>
          </div>

          <div v-else-if="loading || activeId !== null" class="inbox__state" aria-hidden="true">
            <div class="inbox__skeleton-head">
              <q-skeleton type="QAvatar" size="44px" />
              <q-skeleton type="text" width="180px" />
            </div>
          </div>

          <div v-else-if="failed" class="inbox__state" role="alert">
            <h2>Vos messages n’ont pas pu être chargés</h2>
            <p>
              La connexion au réseau a été interrompue. Vos messages sont conservés : réessayez dans
              un instant.
            </p>
            <AppButton variant="outline" label="Réessayer" @click="load()" />
          </div>

          <div v-else-if="!total" class="inbox__state">
            <h2>Écrire à une médiatrice</h2>
            <p>
              Vous n’avez encore échangé avec aucun membre. Trouvez une expertise dans le réseau et
              engagez la conversation.
            </p>
            <div class="inbox__actions">
              <AppButton label="Écrire à une médiatrice" @click="newMessageOpen = true" />
              <router-link :to="{ name: 'network' }" class="link-more">
                Parcourir le réseau
              </router-link>
            </div>
          </div>

          <div v-else class="inbox__state">
            <h2>Les échanges font vivre le réseau</h2>
            <p>
              Choisissez une conversation dans la liste ou écrivez à une médiatrice pour partager
              une expertise, une invitation ou une proposition.
            </p>
            <AppButton variant="outline" label="Nouveau message" @click="newMessageOpen = true" />
          </div>
        </section>
      </q-card>
    </div>

    <NewMessageDialog v-model="newMessageOpen" @open="onNewMessage" />
  </q-page>
</template>

<style lang="scss" scoped>
.messages-page {
  padding-bottom: 64px;
}

// Cadre deux colonnes de hauteur fixe : chaque colonne défile seule
.inbox {
  display: grid;
  grid-template-columns: minmax(300px, 1fr) minmax(0, 2fr);
  height: clamp(560px, calc(100vh - var(--header-h) - 190px), 780px);
  overflow: hidden;

  &__side {
    border-right: 1px solid var(--border);
  }

  &__conversation {
    display: flex;
    flex-direction: column;
    min-width: 0;
    min-height: 0;
    background: var(--card);
  }

  &__state {
    display: grid;
    flex: 1;
    align-content: center;
    justify-items: center;
    gap: 12px;
    padding: 48px 32px;
    text-align: center;

    h2 {
      font-size: var(--fs-h3);
    }

    p {
      max-width: 46ch;
      color: var(--ink-2);
    }
  }

  &__skeleton-head {
    display: flex;
    align-items: center;
    gap: 14px;
    align-self: start;
    justify-self: stretch;
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 4px;
  }
}

@media (max-width: 1080px) {
  .inbox {
    grid-template-columns: minmax(270px, 1fr) minmax(0, 1.6fr);
  }
}

// Téléphone : la liste suit la page ; conversation ouverte en plein écran sous l'en-tête
@media (max-width: 719px) {
  .messages-page {
    padding-bottom: 28px;
  }

  .inbox {
    display: block;
    height: auto;
    overflow: visible;

    &__side {
      border-right: 0;
    }
  }

  .messages-page--open .inbox__conversation {
    position: fixed;
    z-index: 30;
    top: calc(var(--header-h) + env(safe-area-inset-top, 0px));
    right: 0;
    bottom: 0;
    left: 0;
  }
}
</style>
