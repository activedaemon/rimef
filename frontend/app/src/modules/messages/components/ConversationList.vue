<script setup lang="ts">
// Colonne des conversations (maquette Mes messages) : titre et total, « Nouveau message »,
// filtre Toutes / Non lues, lignes (portrait, nom, lieu, extrait, date, non-lus) chargées au
// défilement, et états (chargement, erreur, aucune conversation, aucun résultat, aucun non lu).
// La recherche se fait dans le champ du bandeau (?q=).
import { useTemplateRef } from 'vue';
import type { RouteLocationRaw } from 'vue-router';

import AppButton from '@/components/AppButton.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { shortMoment } from '@/lib/dates';
import type { Conversation, ConversationFilter } from '../services/messages';

defineProps<{
  conversations: Conversation[];
  activeId: number | null;
  /** Lien d'une ligne (garde la recherche en cours). */
  linkTo: (conversation: Conversation) => RouteLocationRaw;
  loading: boolean;
  failed: boolean;
  hasMore: boolean;
  search: string;
  total: number;
  unreadTotal: number;
}>();
const filter = defineModel<ConversationFilter>('filter', { required: true });
const emit = defineEmits<{
  'load-more': [done: (stop?: boolean) => void];
  new: [];
  retry: [];
  'clear-search': [];
}>();

const scroller = useTemplateRef<HTMLElement>('scroller');

function unreadLabel(count: number): string {
  return count > 1 ? `, ${count} messages non lus` : `, ${count} message non lu`;
}
</script>

<template>
  <section class="conversation-list" aria-labelledby="conversation-list-title">
    <div class="conversation-list__head">
      <div class="conversation-list__top">
        <h2 id="conversation-list-title">
          Conversations<span v-if="total" class="conversation-list__total">{{ total }}</span>
        </h2>
        <AppButton
          variant="outline"
          :size="$q.screen.lt.sm ? 'md' : 'sm'"
          icon="pencil"
          label="Nouveau message"
          @click="emit('new')"
        />
      </div>
      <q-btn-toggle
        v-model="filter"
        no-caps
        unelevated
        :ripple="false"
        toggle-color="white"
        toggle-text-color="primary"
        class="conversation-list__filter"
        aria-label="Filtrer les conversations"
        :options="[
          { value: 'all', slot: 'all' },
          { value: 'unread', slot: 'unread' },
        ]"
      >
        <template #all>Toutes</template>
        <template #unread>
          Non lues<span v-if="unreadTotal" class="conversation-list__count">{{ unreadTotal }}</span>
        </template>
      </q-btn-toggle>
    </div>

    <div ref="scroller" class="conversation-list__scroll">
      <!-- Chargement : silhouettes des lignes -->
      <div v-if="loading && !conversations.length" aria-hidden="true">
        <div v-for="row in 5" :key="row" class="conversation-list__skeleton">
          <q-skeleton type="QAvatar" size="36px" />
          <div>
            <q-skeleton type="text" width="55%" />
            <q-skeleton type="text" width="35%" />
            <q-skeleton type="text" width="90%" />
          </div>
        </div>
      </div>
      <p v-if="loading && !conversations.length" class="sr-only">Chargement des conversations…</p>

      <div v-else-if="failed" class="conversation-list__message" role="alert">
        <b>Impossible d’afficher vos conversations.</b>
        Vérifiez votre connexion puis réessayez.
        <q-btn
          flat
          no-caps
          :ripple="false"
          label="Réessayer"
          class="link-more"
          @click="emit('retry')"
        />
      </div>

      <div v-else-if="!conversations.length" class="conversation-list__message" role="status">
        <template v-if="search">
          <b>Aucune conversation ne correspond à « {{ search }} ».</b>
          Vérifiez l’orthographe ou cherchez par prénom, ville ou pays.
          <q-btn
            flat
            no-caps
            :ripple="false"
            label="Effacer la recherche"
            class="link-more"
            @click="emit('clear-search')"
          />
        </template>
        <template v-else-if="filter === 'unread' && total">
          <b>Aucun message non lu.</b>
          Vous êtes à jour dans vos échanges.
          <q-btn
            flat
            no-caps
            :ripple="false"
            label="Voir toutes les conversations"
            class="link-more"
            @click="filter = 'all'"
          />
        </template>
        <template v-else>
          <b>Aucune conversation pour l’instant.</b>
          Vos échanges avec les membres du réseau apparaîtront ici.
          <q-btn
            flat
            no-caps
            :ripple="false"
            label="Écrire à une médiatrice"
            class="link-more"
            @click="emit('new')"
          />
        </template>
      </div>

      <q-infinite-scroll
        v-else
        :scroll-target="$q.screen.lt.sm ? undefined : (scroller ?? undefined)"
        :disable="!hasMore"
        :offset="120"
        @load="(_index: number, done: (stop?: boolean) => void) => emit('load-more', done)"
      >
        <q-list class="conversation-list__rows" aria-label="Conversations">
          <q-item
            v-for="conversation in conversations"
            :key="conversation.id"
            clickable
            :to="linkTo(conversation)"
            :active="conversation.id === activeId"
            :aria-current="conversation.id === activeId ? 'true' : undefined"
            active-class="conversation-row--active"
            class="conversation-row"
            :class="{ 'conversation-row--unread': conversation.unread_count > 0 }"
          >
            <q-item-section avatar top>
              <MemberAvatar
                :name="conversation.contact?.name ?? '?'"
                :photo="conversation.contact?.photo_url ?? null"
                :size="36"
              />
            </q-item-section>
            <q-item-section class="conversation-row__main">
              <span class="conversation-row__who">
                {{ conversation.contact?.name ?? 'Compte supprimé'
                }}<span v-if="conversation.unread_count" class="sr-only">{{
                  unreadLabel(conversation.unread_count)
                }}</span>
              </span>
              <span v-if="conversation.contact?.place" class="conversation-row__place">
                {{ conversation.contact.place }}
              </span>
              <span v-if="conversation.last_message" class="conversation-row__excerpt">
                <span v-if="conversation.last_message.is_mine" class="conversation-row__me"
                  >Vous : </span
                >{{ conversation.last_message.excerpt }}
              </span>
            </q-item-section>
            <q-item-section side top class="conversation-row__end">
              <time v-if="conversation.last_message" :datetime="conversation.last_message.sent_at">
                {{ shortMoment(conversation.last_message.sent_at) }}
              </time>
              <q-badge
                v-if="conversation.unread_count"
                rounded
                class="conversation-row__count"
                :label="conversation.unread_count"
                aria-hidden="true"
              />
            </q-item-section>
          </q-item>
        </q-list>
        <template #loading>
          <div class="conversation-list__more">
            <q-spinner size="20px" color="primary" aria-label="Chargement de la suite" />
          </div>
        </template>
      </q-infinite-scroll>
    </div>
  </section>
</template>

<style lang="scss" scoped>
.conversation-list {
  display: flex;
  flex-direction: column;
  min-height: 0;

  &__head {
    display: grid;
    gap: 12px;
    padding: 18px 18px 14px;
    border-bottom: 1px solid var(--border);
  }

  &__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;

    h2 {
      font-size: 1.3rem;
    }
  }

  &__total,
  &__count {
    margin-left: 6px;
    font-family: var(--font-ui);
    font-size: var(--fs-xs);
    font-weight: 400;
    color: var(--muted);
    font-variant-numeric: tabular-nums;
  }

  // Filtre Toutes / Non lues : segment de la maquette (fond ivoire, choix en blanc)
  &__filter {
    justify-self: start;
    padding: 3px;
    gap: 2px;
    background: var(--ivory);
    border-radius: var(--radius-xs);

    :deep(.q-btn) {
      min-height: 30px;
      padding: 0 12px;
      font-size: var(--fs-xs);
      font-weight: 500;
      color: var(--ink-2);
      border-radius: var(--radius-xs);
    }

    // Choix actif (toggle-color) : fond blanc, texte pétrole, légère ombre
    :deep(.q-btn.bg-white) {
      box-shadow: 0 1px 2px rgba(24, 32, 51, 0.08);
    }
  }

  &__scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  &__skeleton {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 12px;
    padding: 14px 18px;
  }

  &__message {
    display: grid;
    justify-items: start;
    gap: 4px;
    padding: 22px 18px;
    font-size: var(--fs-sm);
    line-height: 1.5;
    color: var(--ink-2);

    b {
      font-weight: 600;
      color: var(--ink);
    }

    .link-more {
      margin-top: 6px;
    }
  }

  &__rows {
    padding: 6px 0;
  }

  &__more {
    display: flex;
    justify-content: center;
    padding: 12px;
  }
}

.conversation-row {
  position: relative;
  align-items: flex-start;
  padding: 14px 18px;
  color: var(--ink);

  & + & {
    box-shadow: inset 0 1px 0 var(--border);
  }

  // Conversation ouverte : fond pétrole léger et filet à gauche
  &--active {
    background: rgba(220, 232, 233, 0.38);
    color: var(--ink);

    &::before {
      content: '';
      position: absolute;
      top: 0;
      bottom: 0;
      left: 0;
      width: 2px;
      background: var(--petrol);
    }
  }

  :deep(.q-item__section--avatar) {
    min-width: 0;
    padding-right: 12px;
  }

  &__main {
    min-width: 0;
  }

  &__who {
    display: block;
    overflow: hidden;
    font-size: var(--fs-sm);
    font-weight: 500;
    line-height: 1.3;
    white-space: nowrap;
    text-overflow: ellipsis;

    .conversation-row--unread & {
      font-weight: 600;
    }
  }

  &__place {
    display: block;
    margin-top: 1px;
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &__excerpt {
    display: -webkit-box;
    margin-top: 6px;
    overflow: hidden;
    font-size: var(--fs-sm);
    line-height: 1.45;
    color: var(--ink-2);
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;

    .conversation-row--unread & {
      color: var(--ink);
    }
  }

  &__me {
    color: var(--muted);
  }

  &__end {
    gap: 8px;
    padding-left: 8px;

    time {
      font-size: var(--fs-xs);
      color: var(--muted);
      white-space: nowrap;
      font-variant-numeric: tabular-nums;
    }
  }

  &__count.q-badge {
    font-size: 11px;
    font-weight: 600;
    color: var(--paper);
    background: var(--terracotta-ink);
  }
}

@media (max-width: 719px) {
  .conversation-list {
    &__head {
      padding: 16px 16px 12px;
    }

    // Téléphone : la liste suit le défilement de la page
    &__scroll {
      overflow: visible;
    }
  }

  .conversation-row {
    padding: 14px 16px;
  }
}
</style>
