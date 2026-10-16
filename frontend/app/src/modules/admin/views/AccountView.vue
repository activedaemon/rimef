<script setup lang="ts">
// Fiche d'un compte (administration), construite comme la fiche d'une médiatrice : en-tête
// ProfileHeader (portrait, nom, rôles, repères) avec les actions à droite (sous l'identité sur
// tablette et téléphone), puis deux colonnes : carte principale (Compte, Invitation, Rôles) et
// petites cartes (Fiche dans le réseau, Actions sensibles). Une action non permise n'est pas
// affichée (UserPolicy) ; les rôles ne sont modifiables que par le superadmin.
import { isAxiosError } from 'axios';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import AppButton from '@/components/AppButton.vue';
import AppTooltip from '@/components/AppTooltip.vue';
import BackLink from '@/components/BackLink.vue';
import EmptyState from '@/components/EmptyState.vue';
import { useNotify } from '@/composables/useNotify';
import { eventNoun, ofName, resourceNoun } from '@/lib/wording';
import { extractApiError } from '@/lib/http';
import ContactDialog from '@modules/network/components/ContactDialog.vue';
import ProfileHeader from '@modules/network/components/ProfileHeader.vue';
import AccountActionDialog, { type AccountAction } from '../components/AccountActionDialog.vue';
import AccountStatusBadge from '../components/AccountStatusBadge.vue';
import {
  ROLE_LABELS,
  fetchAccount,
  isInvitation,
  expiryLabel,
  longDate,
  resendInvitation,
  sortedRoles,
  type Account,
  type AccountRole,
} from '../services/accounts';

const route = useRoute();
const router = useRouter();
const notify = useNotify();

const account = ref<Account | null>(null);
const state = ref<'loading' | 'ready' | 'not-found' | 'error'>('loading');

async function load(): Promise<void> {
  state.value = 'loading';
  try {
    account.value = await fetchAccount(Number(route.params.id));
    state.value = 'ready';
  } catch (error) {
    state.value = isAxiosError(error) && error.response?.status === 404 ? 'not-found' : 'error';
  }
}

watch(
  () => route.params.id,
  () => void load(),
  { immediate: true }
);

const badges = computed(() =>
  sortedRoles(account.value?.roles ?? []).map((role) => ROLE_LABELS[role])
);
const isMember = computed(() => account.value?.roles.includes('member') ?? false);
const badgeTone = computed(() =>
  account.value?.roles.some((role) => role !== 'member') ? 'petrol' : 'sage'
);
const roleLine = computed(() => (isMember.value ? 'Membre du réseau' : 'Équipe RIMeF'));

function moment(iso: string | null): string {
  if (!iso) return '—';
  const time = new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' }).format(
    new Date(iso)
  );
  return `${longDate(iso)}, ${time}`;
}

// Rôles (superadmin) : copie de travail, enregistrée après confirmation
const draftRoles = ref<AccountRole[]>([]);
watch(account, (value) => {
  draftRoles.value = (value?.roles ?? []).filter((role) => role !== 'superadmin');
});
const rolesChanged = computed(() => {
  const current: AccountRole[] = (account.value?.roles ?? []).filter(
    (role) => role !== 'superadmin'
  );
  return (
    draftRoles.value.length !== current.length ||
    draftRoles.value.some((role) => !current.includes(role))
  );
});

/**
 * Carte « Fiche dans le réseau » sans fiche visible : l'invitation passe avant le rôle (un compte invité
 * n'a encore ni fiche ni place dans la messagerie), puis compte désactivé, puis équipe sans fiche.
 */
/** Échéance de l'invitation en clair : « dans 7 jours », « demain », « depuis 3 jours ». */
const expiry = computed(() => {
  const label = account.value ? expiryLabel(account.value) : null;
  if (!label) return null;
  return { relative: label.text.replace(/^Expir(e|ée) /, ''), urgent: label.urgent };
});

const profileNote = computed(() => {
  const value = account.value;
  if (!value) return '';
  if (value.status === 'pending') {
    return isMember.value
      ? 'Cette personne complétera sa fiche après avoir activé son compte.'
      : 'Pas de fiche dans le réseau : une fois le compte activé, il apparaîtra comme « Équipe RIMeF » dans la messagerie.';
  }
  if (value.status === 'expired') {
    return isMember.value
      ? 'L’invitation a expiré : renvoyez-la pour permettre d’activer le compte et de compléter la fiche.'
      : 'L’invitation a expiré : renvoyez-la pour permettre d’activer le compte.';
  }
  if (isMember.value) {
    return 'Sa fiche n’apparaît plus dans l’annuaire tant que le compte est désactivé.';
  }
  return 'Pas de fiche dans le réseau : ce compte apparaît comme « Équipe RIMeF » dans la messagerie.';
});

const canWrite = computed(() => !!account.value?.can.send_message);
const hasActions = computed(() => {
  const can = account.value?.can;
  return !!can && (canWrite.value || can.manage_invitation || can.toggle_activation);
});

// Fenêtres
const actionOpen = ref(false);
const action = ref<AccountAction>('deactivate');
const contactOpen = ref(false);

function openAction(kind: AccountAction): void {
  action.value = kind;
  actionOpen.value = true;
}

function onActionDone(updated: Account | null): void {
  if (updated) account.value = updated;
  else void router.replace({ name: 'admin' });
}

const resending = ref(false);

// Délai entre deux envois : bouton grisé jusqu'à l'heure de renvoi, réactivé sans recharger
const now = ref(Date.now());
const clock = window.setInterval(() => (now.value = Date.now()), 15_000);
onBeforeUnmount(() => window.clearInterval(clock));

function timeOf(iso: string): string {
  return new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' }).format(
    new Date(iso)
  );
}

const resendLocked = computed(() => {
  const at = account.value?.invitation_resendable_at;
  return !!at && new Date(at).getTime() > now.value;
});
const resendHint = computed(() => {
  const value = account.value;
  if (!resendLocked.value || !value?.invited_at || !value.invitation_resendable_at) return '';
  return `Envoyée à ${timeOf(value.invited_at)} · renvoi possible à partir de ${timeOf(value.invitation_resendable_at)}`;
});
async function resend(): Promise<void> {
  if (!account.value) return;
  resending.value = true;
  try {
    account.value = await resendInvitation(account.value.id);
    notify.success(`Invitation renvoyée à ${account.value.email}`);
  } catch (error) {
    notify.error(extractApiError(error, 'L’invitation n’a pas pu être renvoyée. Réessayez.'));
  } finally {
    resending.value = false;
  }
}
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <BackLink :to="{ name: 'admin' }" label="Retour aux comptes" class="account-back" />
    </div>

    <div v-if="state === 'loading'" class="container account-state">
      <q-spinner color="primary" size="32px" aria-label="Chargement du compte" />
    </div>

    <div v-else-if="state === 'not-found'" class="container account-message">
      <EmptyState
        icon="user-question"
        title="Compte introuvable"
        text="Ce compte n’existe plus ou son lien est incorrect. Il a peut-être été supprimé."
      >
        <AppButton variant="outline" label="Voir tous les comptes" :to="{ name: 'admin' }" />
      </EmptyState>
    </div>

    <div v-else-if="state === 'error' || !account" class="container account-message">
      <EmptyState
        icon="alert-circle"
        title="Ce compte n’a pas pu être chargé"
        text="La connexion a été interrompue. Réessayez dans un instant."
      >
        <AppButton variant="outline" label="Réessayer" @click="load" />
      </EmptyState>
    </div>

    <template v-else>
      <div class="container">
        <ProfileHeader :profile="account" :badges="badges" :badge-tone="badgeTone" :role="roleLine">
          <template #facts>
            <li class="meta account-fact">
              <q-icon name="mail" size="15px" aria-hidden="true" />{{ account.email }}
            </li>
            <li v-if="account.is_protected" class="meta">
              <q-icon name="lock" size="15px" aria-hidden="true" />Compte protégé
            </li>
          </template>

          <template v-if="hasActions" #actions>
            <AppButton
              v-if="canWrite"
              variant="accent"
              icon="mail"
              :label="`Écrire à ${account.first_name}`"
              @click="contactOpen = true"
            />
            <template v-if="account.can.manage_invitation">
              <AppButton
                icon="refresh"
                label="Renvoyer l’invitation"
                :loading="resending"
                :disable="resendLocked"
                :aria-describedby="resendLocked ? 'resend-hint' : undefined"
                @click="resend"
              />
              <p v-if="resendLocked" id="resend-hint" class="account-resend-hint">
                {{ resendHint }}
              </p>
              <AppButton
                variant="quiet"
                label="Annuler l’invitation"
                @click="openAction('cancel')"
              />
            </template>
            <AppButton
              v-if="account.can.toggle_activation"
              variant="quiet"
              :icon="account.status === 'active' ? 'player-pause' : 'player-play'"
              :label="account.status === 'active' ? 'Désactiver le compte' : 'Réactiver le compte'"
              @click="openAction(account.status === 'active' ? 'deactivate' : 'reactivate')"
            />
          </template>
        </ProfileHeader>
      </div>

      <div class="container"><div class="account-band" aria-hidden="true" /></div>

      <div class="container account-body">
        <q-card flat tag="article" class="rimef-card account-main">
          <section class="account-section" aria-labelledby="account-title">
            <h2 id="account-title">Compte</h2>
            <dl class="account-facts">
              <div>
                <dt>Adresse email</dt>
                <dd class="account-facts__email">{{ account.email }}</dd>
              </div>
              <div>
                <dt>Statut</dt>
                <dd><AccountStatusBadge :status="account.status" /></dd>
              </div>
              <template v-if="!isInvitation(account)">
                <div>
                  <dt>Dernière connexion</dt>
                  <dd>
                    {{ account.last_login_at ? moment(account.last_login_at) : 'Aucune connexion' }}
                  </dd>
                </div>
                <div>
                  <dt>Membre depuis</dt>
                  <dd>{{ account.member_since ? longDate(account.member_since) : '—' }}</dd>
                </div>
              </template>
            </dl>
          </section>

          <section
            v-if="isInvitation(account)"
            class="account-section"
            aria-labelledby="invitation-title"
          >
            <h2 id="invitation-title">Invitation</h2>
            <dl class="account-facts">
              <div>
                <dt>Envoyée le</dt>
                <dd>
                  {{ account.invited_at ? moment(account.invited_at) : '—'
                  }}<template v-if="account.invited_by"> par {{ account.invited_by }}</template>
                </dd>
              </div>
              <div>
                <dt>{{ account.status === 'expired' ? 'Expirée le' : 'Expire le' }}</dt>
                <dd>
                  {{
                    account.invitation_expires_at ? longDate(account.invitation_expires_at) : '—'
                  }}
                  <span
                    v-if="expiry"
                    class="account-expiry"
                    :class="{ 'account-expiry--urgent': expiry.urgent }"
                    >({{ expiry.relative }})</span
                  >
                </dd>
              </div>
            </dl>
          </section>

          <section class="account-section" aria-labelledby="roles-title">
            <h2 id="roles-title">Rôles</h2>
            <fieldset v-if="account.can.update_roles" class="account-roles">
              <legend class="sr-only">Rôles du compte</legend>
              <q-checkbox v-model="draftRoles" val="member" class="account-roles__option">
                <span
                  ><b>Membre</b
                  ><small>Fiche dans le réseau et présence dans l’annuaire.</small></span
                >
              </q-checkbox>
              <q-checkbox v-model="draftRoles" val="admin" class="account-roles__option">
                <span
                  ><b>Administratrice</b><small>Accès à la rubrique Administration.</small></span
                >
              </q-checkbox>
              <AppButton
                variant="outline"
                size="sm"
                label="Enregistrer les rôles"
                :disable="!rolesChanged || draftRoles.length === 0"
                class="account-roles__save"
                @click="openAction('roles')"
              />
            </fieldset>
            <template v-else>
              <ul class="account-chips">
                <li v-for="badge in badges" :key="badge">
                  <q-chip :ripple="false" class="account-chip">{{ badge }}</q-chip>
                </li>
              </ul>
              <p
                v-if="!account.is_protected && !account.is_self && !isInvitation(account)"
                class="account-hint"
              >
                Seul le superadministrateur peut modifier les rôles.
              </p>
            </template>
          </section>
        </q-card>

        <aside class="account-side" :aria-label="`En lien avec ${account.name}`">
          <q-card
            flat
            tag="section"
            class="rimef-card side-card"
            aria-labelledby="profile-link-title"
          >
            <div class="card-head">
              <h2 id="profile-link-title" class="card-title">Fiche dans le réseau</h2>
            </div>
            <template v-if="account.profile_summary">
              <div class="profile-state">
                <AppTooltip v-if="account.profile_summary.missing.length">
                  Manquent : {{ account.profile_summary.missing.join(', ') }}
                </AppTooltip>
                <p class="profile-state__completion">
                  Fiche complétée à <b>{{ account.profile_summary.completion }} %</b>
                </p>
                <q-linear-progress
                  :value="account.profile_summary.completion / 100"
                  rounded
                  size="6px"
                  class="profile-state__bar"
                  :aria-label="`Fiche complétée à ${account.profile_summary.completion} %`"
                />
              </div>
              <ul class="profile-state__facts">
                <li>
                  <q-icon
                    :name="account.profile_summary.visible ? 'eye' : 'eye-off'"
                    size="16px"
                    aria-hidden="true"
                  />
                  {{
                    account.profile_summary.visible
                      ? 'Visible dans l’annuaire'
                      : 'Masquée : compte désactivé'
                  }}
                </li>
                <li v-if="account.profile_summary.updated_at">
                  <q-icon name="pencil" size="16px" aria-hidden="true" />
                  Modifiée le {{ longDate(account.profile_summary.updated_at) }}
                </li>
                <li>
                  <q-icon name="calendar" size="16px" aria-hidden="true" />
                  {{ account.profile_summary.upcoming_events }}
                  {{ eventNoun(account.profile_summary.upcoming_events) }} à venir
                </li>
                <li>
                  <q-icon name="book" size="16px" aria-hidden="true" />
                  {{ account.profile_summary.resources }}
                  {{ resourceNoun(account.profile_summary.resources) }}
                </li>
              </ul>
            </template>
            <router-link
              v-if="account.profile_slug"
              :to="{ name: 'member', params: { slug: account.profile_slug } }"
              class="link-more account-side__link"
            >
              Voir la fiche {{ ofName(account.first_name) }}
            </router-link>
            <p v-else-if="!account.profile_summary" class="side-card__empty">{{ profileNote }}</p>
            <p v-if="account.status === 'inactive'" class="side-card__empty account-side__note">
              Ce compte ne peut plus se connecter. Ses conversations et participations sont
              conservées.
            </p>
          </q-card>

          <q-card
            v-if="account.can.delete"
            flat
            tag="section"
            class="rimef-card side-card account-danger"
            aria-labelledby="danger-title"
          >
            <div class="card-head">
              <h2 id="danger-title" class="card-title">Actions sensibles</h2>
            </div>
            <p class="account-danger__text">
              La suppression efface définitivement le compte et ses données personnelles. À réserver
              aux demandes de la personne ou aux comptes créés par erreur ; sinon, préférez la
              désactivation.
            </p>
            <AppButton
              variant="quiet"
              icon="trash"
              label="Supprimer définitivement"
              class="account-danger__button"
              @click="openAction('delete')"
            />
          </q-card>
        </aside>
      </div>

      <AccountActionDialog
        v-model="actionOpen"
        :account="account"
        :action="action"
        :next-roles="draftRoles"
        @done="onActionDone"
      />
      <ContactDialog
        v-if="canWrite"
        v-model="contactOpen"
        :profile="{
          account_id: account.id,
          slug: account.profile_slug,
          name: account.name,
          first_name: account.first_name,
          photo_url: account.photo_url,
        }"
      />
    </template>
  </q-page>
</template>

<style lang="scss" scoped>
.account-back {
  margin-top: var(--s-4);
}

.account-state {
  display: grid;
  place-items: center;
  padding-block: var(--s-16);
}

.account-message {
  margin-top: var(--s-4);
}

.account-fact {
  overflow-wrap: anywhere;
}

.account-resend-hint {
  margin-top: -4px;
  font-size: var(--fs-xs);
  line-height: 1.5;
  color: var(--muted);
}

.account-expiry {
  color: var(--muted);

  // Expire aujourd'hui ou demain : à relancer
  &--urgent {
    font-weight: 600;
    color: var(--terracotta-ink);
  }
}

// Filet sous l'en-tête, à la largeur du contenu, comme celui des onglets de la fiche d'une médiatrice
.account-band {
  border-bottom: 1px solid var(--border);
}

.account-body {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 360px;
  gap: 24px;
  align-items: start;
  padding-block: var(--s-6) var(--s-12);
}

// Ordinateur : la carte s'étire à la hauteur de la colonne de droite (bas des colonnes alignés) ;
// ses sections restent en haut
.account-main {
  align-self: stretch;
  align-content: start;
  display: grid;
  gap: 30px;
  padding: 34px 38px 38px;
}

.account-section {
  h2 {
    margin-bottom: 12px;
    font-size: 1.45rem;
  }

  & + & {
    padding-top: 26px;
    border-top: 1px solid var(--border);
  }
}

.account-facts {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px 32px;
  margin: 0;

  dt {
    font-size: var(--fs-sm);
    color: var(--muted);
  }

  dd {
    margin: 2px 0 0;
    color: var(--ink);
    font-variant-numeric: tabular-nums;
  }

  &__email {
    overflow-wrap: anywhere;
  }
}

.account-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0;
  padding: 0;
  list-style: none;
}

// Étiquette pétrole claire, comme les expertises de la fiche
.account-chip.q-chip {
  min-height: 30px;
  height: auto;
  margin: 0;
  padding: 4px 12px;
  border-radius: var(--radius-pill);
  background: var(--petrol-light);
  font-size: var(--fs-sm);
  line-height: 1.3;
  color: var(--petrol);
}

.account-hint {
  margin-top: 12px;
  font-size: var(--fs-sm);
  color: var(--ink-2);
}

.account-roles {
  display: grid;
  margin: 0;
  padding: 0;
  border: 0;
  min-width: 0;

  &__option {
    min-height: 44px;
    margin-left: -10px;

    b {
      display: block;
      font-weight: 500;
      color: var(--ink);
    }

    small {
      display: block;
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }

  &__save {
    justify-self: start;
    margin-top: 20px;
  }
}

// Carte « Fiche dans le réseau » : complétude, visibilité, participation
.profile-state {
  display: grid;
  gap: 6px;

  &__completion {
    font-size: var(--fs-sm);
    color: var(--ink-2);

    b {
      color: var(--petrol);
    }
  }

  // Nuances de bleu pétrole, comme les badges de l'équipe
  &__bar {
    color: var(--petrol);

    :deep(.q-linear-progress__track) {
      background: var(--petrol-light);
      opacity: 1;
    }
  }

  &__facts {
    display: grid;
    gap: 8px;
    margin: 16px 0 4px;
    padding: 14px 0 0;
    border-top: 1px solid var(--border);
    list-style: none;
    font-size: var(--fs-sm);
    color: var(--ink-2);

    li {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .q-icon {
      color: var(--muted);
    }
  }
}

.account-side {
  position: sticky;
  top: calc(var(--header-h) + 20px + env(safe-area-inset-top, 0px));
  display: grid;
  gap: 20px;

  &__link {
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    font-size: var(--fs-sm);
  }

  &__note {
    margin-top: 10px;
  }
}

.side-card {
  padding: 24px 24px 20px;

  .card-head {
    margin-bottom: 14px;
  }

  .card-title {
    font-size: 1.3rem;
  }

  &__empty {
    font-size: var(--fs-sm);
    color: var(--muted);
  }
}

.account-danger {
  &__text {
    font-size: var(--fs-sm);
    line-height: 1.65;
    color: var(--ink-2);
  }

  &__button.app-btn {
    width: 100%;
    margin-top: var(--s-4);
    border-color: var(--terracotta-ink);
    color: var(--terracotta-ink);
  }
}

// Tablette : colonne latérale sous la carte, ses cartes côte à côte (comme la fiche)
@media (max-width: 1079px) {
  .account-body {
    grid-template-columns: 1fr;
  }

  .account-side {
    position: static;
    grid-template-columns: 1fr 1fr;
    align-items: start;
  }

  .account-main {
    padding: 26px 28px 30px;
  }
}

// Téléphone : tout empilé
@media (max-width: 719px) {
  .account-body {
    padding-block: var(--s-4) var(--s-8);
  }

  .account-main {
    padding: 22px 18px 24px;
  }

  .account-facts {
    grid-template-columns: 1fr;
    gap: 14px;
  }

  .account-side {
    grid-template-columns: 1fr;
  }
}
</style>
