<script setup lang="ts">
// Fenêtre « Nouveau message » (maquette Mes messages), sœur de « Contacter » :
// 1. choisir la destinataire parmi les médiatrices de l'annuaire (recherche par nom) ;
//    si une conversation existe déjà avec elle, elle s'ouvre directement ;
// 2. objet de la demande et message (ContactForm).
// Émet `open` avec la conversation à afficher.
import { useQuasar } from 'quasar';
import { computed, nextTick, ref, watch } from 'vue';

import MemberAvatar from '@/components/MemberAvatar.vue';
import { useSession } from '@/stores/session';
import type { Member } from '@modules/network/services/members';
import { searchRecipients } from '../services/messages';
import ContactForm from './ContactForm.vue';

const open = defineModel<boolean>({ required: true });
const emit = defineEmits<{ open: [conversationId: number] }>();

const $q = useQuasar();
const session = useSession();

const query = ref('');
const results = ref<Member[]>([]);
const searching = ref(false);
const recipient = ref<Member | null>(null);
const searchInput = ref<{ focus: () => void } | null>(null);
const form = ref<{ focus: () => void } | null>(null);
let controller: AbortController | null = null;

const recipientFirstName = computed(() => recipient.value?.name.split(' ')[0] ?? '');

async function search(): Promise<void> {
  controller?.abort();
  const current = (controller = new AbortController());
  searching.value = true;
  try {
    const members = await searchRecipients(query.value, current.signal);
    results.value = members.filter((member) => member.id !== session.user?.id);
  } catch {
    if (!current.signal.aborted) results.value = [];
  } finally {
    if (controller === current) searching.value = false;
  }
}

function pick(member: Member): void {
  if (member.conversation_id !== null) {
    open.value = false;
    emit('open', member.conversation_id);
    return;
  }
  recipient.value = member;
  void nextTick(() => form.value?.focus());
}

function pickFirst(): void {
  const first = results.value[0];
  if (first) pick(first);
}

function changeRecipient(): void {
  recipient.value = null;
  void nextTick(() => searchInput.value?.focus());
}

function onSent(conversationId: number): void {
  open.value = false;
  emit('open', conversationId);
}

// Nouvelle fenêtre : étape 1, recherche vide (premières médiatrices par ordre alphabétique)
watch(open, (value) => {
  if (!value) return;
  query.value = '';
  recipient.value = null;
  void search();
});
</script>

<template>
  <q-dialog
    v-model="open"
    :position="$q.screen.lt.sm ? 'bottom' : 'standard'"
    @show="searchInput?.focus()"
  >
    <q-card
      class="message-dialog new-message"
      role="dialog"
      :aria-labelledby="recipient ? 'new-message-title-2' : 'new-message-title'"
    >
      <template v-if="!recipient">
        <div class="message-dialog__head">
          <span class="new-message__icon" aria-hidden="true"><q-icon name="pencil" /></span>
          <div>
            <h2 id="new-message-title">Nouveau message</h2>
            <p>Écrivez à une médiatrice du réseau</p>
          </div>
          <q-btn
            v-close-popup
            flat
            round
            icon="x"
            aria-label="Fermer"
            class="message-dialog__close"
          />
        </div>

        <label class="field-label" for="new-message-search">Destinataire</label>
        <q-input
          ref="searchInput"
          v-model="query"
          for="new-message-search"
          type="search"
          outlined
          dense
          debounce="250"
          placeholder="Rechercher une médiatrice par son nom…"
          class="new-message__search"
          @update:model-value="search"
          @keydown.enter.prevent="pickFirst"
        >
          <template #prepend><q-icon name="search" size="18px" /></template>
          <template v-if="searching" #append><q-spinner size="16px" color="primary" /></template>
        </q-input>

        <q-list class="new-message__results" aria-label="Médiatrices">
          <q-item
            v-for="member in results"
            :key="member.id"
            clickable
            class="new-message__option"
            @click="pick(member)"
          >
            <q-item-section avatar>
              <MemberAvatar :name="member.name" :photo="member.photo_url" :size="40" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="new-message__name">{{ member.name }}</q-item-label>
              <q-item-label caption class="new-message__meta">
                {{ member.country?.name
                }}<template v-if="member.country && member.expertises[0]"> · </template
                ><em v-if="member.expertises[0]">{{ member.expertises[0] }}</em>
              </q-item-label>
            </q-item-section>
            <q-item-section v-if="member.conversation_id !== null" side>
              <q-badge
                outline
                class="rimef-badge new-message__badge"
                label="Conversation en cours"
              />
            </q-item-section>
          </q-item>
          <q-item v-if="!searching && !results.length" class="new-message__empty">
            <q-item-section
              >Aucune médiatrice ne correspond à « {{ query.trim() }} ».</q-item-section
            >
          </q-item>
        </q-list>

        <p class="new-message__hint">Si vous échangez déjà avec elle, la conversation s’ouvre.</p>
      </template>

      <template v-else>
        <div class="message-dialog__head">
          <MemberAvatar :name="recipient.name" :photo="recipient.photo_url" :size="48" />
          <div>
            <h2 id="new-message-title-2">Écrire à {{ recipientFirstName }}</h2>
            <p>Message transmis via la messagerie du réseau</p>
          </div>
          <q-btn
            v-close-popup
            flat
            round
            icon="x"
            aria-label="Fermer"
            class="message-dialog__close"
          />
        </div>

        <ContactForm
          ref="form"
          :recipient="{ slug: recipient.slug, first_name: recipientFirstName }"
          @sent="onSent"
        >
          <template #secondary>
            <q-btn
              flat
              no-caps
              :ripple="false"
              label="Changer de destinataire"
              class="link-more"
              @click="changeRecipient"
            />
          </template>
        </ContactForm>
      </template>
    </q-card>
  </q-dialog>
</template>

<style lang="scss" scoped>
.new-message {
  &__icon {
    display: grid;
    flex: none;
    place-items: center;
    width: 48px;
    height: 48px;
    font-size: 22px;
    color: var(--petrol);
    background: var(--petrol-light);
    border-radius: 50%;
  }

  &__search {
    margin-top: 6px;
  }

  &__results {
    max-height: min(46vh, 340px);
    margin: 8px -8px 0;
    overflow-y: auto;
  }

  &__option {
    min-height: 56px;
    padding: 8px;
    border-radius: var(--radius-xs);
  }

  &__name {
    font-size: var(--fs-sm);
    font-weight: 600;
    color: var(--ink);
  }

  &__meta {
    font-size: var(--fs-xs);
    color: var(--muted);

    em {
      font-style: normal;
      color: var(--petrol);
    }
  }

  &__badge {
    color: var(--ink-2);
    border-color: var(--border);
    white-space: nowrap;
  }

  &__empty {
    font-size: var(--fs-sm);
    color: var(--ink-2);
  }

  &__hint {
    margin-top: var(--s-4);
    padding-top: var(--s-3);
    font-size: var(--fs-xs);
    color: var(--muted);
    border-top: 1px solid var(--border);
  }
}
</style>
