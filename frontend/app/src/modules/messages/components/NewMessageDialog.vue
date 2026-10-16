<script setup lang="ts">
// Fenêtre « Nouveau message » (maquette Mes messages), sœur de « Contacter » :
// 1. choisir la destinataire : section « Équipe RIMeF » (administratrices et superadmin, avec
//    leur rôle ; en tête sans recherche, après les médiatrices pendant une recherche) et
//    médiatrices de l'annuaire (recherche par nom) ; une conversation existante s'ouvre ;
// 2. objet de la demande et message (ContactForm).
// Émet `open` avec la conversation à afficher.
import { useQuasar } from 'quasar';
import { computed, nextTick, ref, watch } from 'vue';

import MemberAvatar from '@/components/MemberAvatar.vue';
import { useSession } from '@/stores/session';
import type { Member } from '@modules/network/services/members';
import { fetchTeam, searchRecipients, teamRoleLabel, type TeamMember } from '../services/messages';
import ContactForm from './ContactForm.vue';

const open = defineModel<boolean>({ required: true });
const emit = defineEmits<{ open: [conversationId: number] }>();

const $q = useQuasar();
const session = useSession();

const query = ref('');
const results = ref<Member[]>([]);
const searching = ref(false);
const team = ref<TeamMember[]>([]);

/** Destinataire choisie (médiatrice ou personne de l'équipe). */
interface Recipient {
  id: number;
  slug: string;
  name: string;
  photo_url: string | null;
  conversation_id: number | null;
}
const recipient = ref<Recipient | null>(null);
const searchInput = ref<{ focus: () => void } | null>(null);
const form = ref<{ focus: () => void } | null>(null);
let controller: AbortController | null = null;

const recipientFirstName = computed(() => recipient.value?.name.split(' ')[0] ?? '');

function normalize(text: string): string {
  return text
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();
}

/** Équipe filtrée par la recherche (nom ou rôle). */
const teamResults = computed(() => {
  const words = normalize(query.value).split(/\s+/).filter(Boolean);
  return team.value.filter((person) => {
    const text = normalize(`${person.name} ${teamRoleLabel(person.roles) ?? ''} equipe rimef`);
    return words.every((word) => text.includes(word));
  });
});
const teamFirst = computed(() => query.value.trim() === '');

async function loadTeam(): Promise<void> {
  try {
    team.value = await fetchTeam();
  } catch {
    team.value = [];
  }
}

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

function pick(person: Recipient): void {
  if (person.conversation_id !== null) {
    open.value = false;
    emit('open', person.conversation_id);
    return;
  }
  recipient.value = person;
  void nextTick(() => form.value?.focus());
}

function pickFirst(): void {
  const first = teamFirst.value
    ? (teamResults.value[0] ?? results.value[0])
    : (results.value[0] ?? teamResults.value[0]);
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
  void loadTeam();
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
            <p>Écrivez à une médiatrice ou à l’équipe du réseau</p>
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
          placeholder="Rechercher une personne par son nom…"
          class="new-message__search"
          @update:model-value="search"
          @keydown.enter.prevent="pickFirst"
        >
          <template #prepend><q-icon name="search" size="18px" /></template>
          <template v-if="searching" #append><q-spinner size="16px" color="primary" /></template>
        </q-input>

        <q-list class="new-message__results" aria-label="Destinataires">
          <template
            v-for="section in teamFirst ? ['team', 'members'] : ['members', 'team']"
            :key="section"
          >
            <template v-if="section === 'team' && teamResults.length">
              <q-item-label header class="new-message__section">
                Équipe RIMeF<small>Pour une question sur le réseau ou votre compte</small>
              </q-item-label>
              <q-item
                v-for="person in teamResults"
                :key="`team-${person.id}`"
                clickable
                class="new-message__option"
                @click="pick(person)"
              >
                <q-item-section avatar>
                  <MemberAvatar :name="person.name" :photo="person.photo_url" :size="40" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="new-message__name">{{ person.name }}</q-item-label>
                  <q-item-label caption class="new-message__meta">
                    <span class="new-message__team">Équipe RIMeF</span> ·
                    {{ teamRoleLabel(person.roles) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section v-if="person.conversation_id !== null" side>
                  <q-badge
                    outline
                    class="rimef-badge new-message__badge"
                    label="Conversation en cours"
                  />
                </q-item-section>
              </q-item>
            </template>

            <template v-if="section === 'members'">
              <q-item-label
                v-if="teamResults.length && results.length"
                header
                class="new-message__section"
              >
                Médiatrices
              </q-item-label>
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
            </template>
          </template>
          <q-item
            v-if="!searching && !results.length && !teamResults.length"
            class="new-message__empty"
          >
            <q-item-section>Personne ne correspond à « {{ query.trim() }} ».</q-item-section>
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
          :recipient="{ id: recipient.id, slug: recipient.slug, first_name: recipientFirstName }"
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

  &__section {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 2px 10px;
    padding: 12px 8px 4px;
    font-size: var(--fs-eyebrow);
    font-weight: 600;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--ink-2);

    small {
      font-size: var(--fs-xs);
      font-weight: 400;
      letter-spacing: 0;
      text-transform: none;
      color: var(--muted);
    }
  }

  // Équipe RIMeF : pétrole, comme son badge dans la conversation
  &__team {
    font-weight: 600;
    color: var(--petrol);
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
