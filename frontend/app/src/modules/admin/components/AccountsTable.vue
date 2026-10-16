<script setup lang="ts">
// Liste des comptes (fiche AccountRow) : q-table sur ordinateur et tablette (colonne « Membre depuis »
// masquée sous 1080 px), cartes sur téléphone (mode grid). Un clic n'importe où sur la ligne ou
// la carte ouvre la fiche du compte, où se trouvent les actions ; le nom reste un vrai lien
// (clavier, Ctrl ou Cmd + clic pour un nouvel onglet).
import { useQuasar } from 'quasar';
import { computed } from 'vue';
import { useRouter } from 'vue-router';

import MemberAvatar from '@/components/MemberAvatar.vue';
import {
  activityLabel,
  expiryLabel,
  isInvitation,
  longDate,
  shortDate,
  type Account,
} from '../services/accounts';
import AccountRoleBadges from './AccountRoleBadges.vue';
import AccountStatusBadge from './AccountStatusBadge.vue';
import AppTooltip from '@/components/AppTooltip.vue';

defineProps<{ accounts: Account[]; busy?: boolean }>();

const $q = useQuasar();
const router = useRouter();

const columns = computed(() => [
  { name: 'account', label: 'Compte', field: 'name', align: 'left' as const },
  { name: 'roles', label: 'Rôles', field: 'roles', align: 'left' as const },
  { name: 'status', label: 'Statut', field: 'status', align: 'left' as const },
  { name: 'completion', label: 'Fiche', field: 'profile_completion', align: 'left' as const },
  {
    name: 'last_login',
    label: 'Activité',
    field: 'last_login_at',
    align: 'left' as const,
  },
  ...($q.screen.lt.md
    ? []
    : [{ name: 'created', label: 'Membre depuis', field: 'member_since', align: 'left' as const }]),
]);

function accountRoute(account: Account) {
  return { name: 'admin-account', params: { id: account.id } };
}

/** Clic hors du nom : même destination que le lien (le lien gère lui-même ses clics). */
function openAccount(account: Account, event: MouseEvent): void {
  if ((event.target as HTMLElement).closest('a')) return;
  if (window.getSelection()?.toString()) return; // texte sélectionné (copier un email)
  if (event.ctrlKey || event.metaKey) {
    window.open(router.resolve(accountRoute(account)).href, '_blank', 'noopener');
    return;
  }
  void router.push(accountRoute(account));
}
</script>

<template>
  <q-table
    :rows="accounts"
    :columns="columns"
    row-key="id"
    flat
    hide-pagination
    :rows-per-page-options="[0]"
    :grid="$q.screen.lt.sm"
    class="accounts-table"
    :class="{ 'accounts-table--busy': busy }"
    :card-container-class="'accounts-table__cards'"
  >
    <template #body="{ row }">
      <q-tr
        class="accounts-table__row"
        :class="`accounts-table__row--${row.status}`"
        @click="openAccount(row, $event)"
      >
        <q-td key="account">
          <div class="accounts-table__who">
            <span class="accounts-table__avatar">
              <MemberAvatar
                :name="row.name"
                :photo="row.photo_url"
                :size="36"
                :class="{ 'accounts-table__avatar--invited': isInvitation(row) }"
              />
              <AppTooltip v-if="isInvitation(row)">Compte pas encore activé</AppTooltip>
            </span>
            <div class="accounts-table__identity">
              <router-link :to="accountRoute(row)" class="accounts-table__name">{{
                row.name
              }}</router-link>
              <span v-if="row.is_self" class="accounts-table__you">Vous</span>
              <small>{{ row.email }}</small>
            </div>
          </div>
        </q-td>
        <q-td key="roles">
          <span class="accounts-table__roles">
            <AccountRoleBadges :roles="row.roles" />
            <span v-if="row.is_protected" class="accounts-table__protected">
              <q-icon name="lock" size="14px" aria-hidden="true" />
              <span class="sr-only">Compte protégé</span>
              <q-tooltip>Compte protégé</q-tooltip>
            </span>
          </span>
        </q-td>
        <q-td key="status">
          <AccountStatusBadge :status="row.status">
            <template v-if="expiryLabel(row)">
              <span class="sr-only">, {{ expiryLabel(row)?.text }}</span>
              <q-tooltip>
                {{ expiryLabel(row)?.text }}
                <template v-if="row.invitation_expires_at">
                  ({{ longDate(row.invitation_expires_at) }})
                </template>
              </q-tooltip>
            </template>
          </AccountStatusBadge>
        </q-td>
        <q-td key="completion">
          <span v-if="row.profile_completion" class="accounts-table__completion">
            <q-linear-progress
              :value="row.profile_completion.percent / 100"
              rounded
              size="6px"
              class="accounts-table__bar"
              aria-hidden="true"
            />
            {{ row.profile_completion.percent }} %
            <AppTooltip v-if="row.profile_completion.missing.length">
              Manquent : {{ row.profile_completion.missing.join(', ') }}
            </AppTooltip>
          </span>
          <template v-else>—</template>
        </q-td>
        <q-td key="last_login">{{ activityLabel(row) }}</q-td>
        <q-td v-if="!$q.screen.lt.md" key="created">{{
          row.member_since ? shortDate(row.member_since) : '—'
        }}</q-td>
      </q-tr>
    </template>

    <template #item="{ row }">
      <article
        class="accounts-table__card"
        :class="`accounts-table__row--${row.status}`"
        @click="openAccount(row, $event)"
      >
        <div class="accounts-table__card-head">
          <div class="accounts-table__who">
            <span class="accounts-table__avatar">
              <MemberAvatar
                :name="row.name"
                :photo="row.photo_url"
                :size="36"
                :class="{ 'accounts-table__avatar--invited': isInvitation(row) }"
              />
              <AppTooltip v-if="isInvitation(row)">Compte pas encore activé</AppTooltip>
            </span>
            <div class="accounts-table__identity">
              <router-link :to="accountRoute(row)" class="accounts-table__name">{{
                row.name
              }}</router-link>
              <span v-if="row.is_self" class="accounts-table__you">Vous</span>
              <small class="accounts-table__email">{{ row.email }}</small>
            </div>
          </div>
        </div>
        <div>
          <span class="accounts-table__roles">
            <AccountRoleBadges :roles="row.roles" />
            <span v-if="row.is_protected" class="accounts-table__protected">
              <q-icon name="lock" size="14px" aria-hidden="true" />
              <span class="sr-only">Compte protégé</span>
              <q-tooltip>Compte protégé</q-tooltip>
            </span>
          </span>
        </div>
        <div class="accounts-table__card-foot">
          <div>
            <AccountStatusBadge :status="row.status">
              <template v-if="expiryLabel(row)">
                <span class="sr-only">, {{ expiryLabel(row)?.text }}</span>
                <q-tooltip>
                  {{ expiryLabel(row)?.text }}
                  <template v-if="row.invitation_expires_at">
                    ({{ longDate(row.invitation_expires_at) }})
                  </template>
                </q-tooltip>
              </template>
            </AccountStatusBadge>
          </div>
          <p v-if="row.profile_completion" class="accounts-table__last">
            <span>Fiche</span>{{ row.profile_completion.percent }} %
          </p>
          <p class="accounts-table__last"><span>Activité</span>{{ activityLabel(row) }}</p>
        </div>
      </article>
    </template>
  </q-table>
</template>

<style lang="scss" scoped>
.accounts-table {
  margin-top: 18px;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  transition: opacity 0.15s;

  &--busy {
    opacity: 0.55;
  }

  :deep(thead th) {
    padding: 12px 16px;
    font-size: var(--fs-eyebrow);
    font-weight: 500;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--muted);
  }

  :deep(tbody td) {
    padding: 12px 16px;
    height: auto;
    font-size: var(--fs-sm);
    color: var(--ink-2);
    font-variant-numeric: tabular-nums;
    border-color: var(--border);
  }

  &__row,
  &__card {
    cursor: pointer;
  }

  &__row:hover {
    background: #fbf8f2;
  }

  // Complétude de la fiche : mini-barre pétrole et pourcentage
  &__completion {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
  }

  &__bar {
    width: 56px;
    color: var(--petrol);

    :deep(.q-linear-progress__track) {
      background: var(--petrol-light);
      opacity: 1;
    }
  }

  &__avatar {
    display: inline-flex;
    flex: none;
  }

  &__who {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
  }

  &__identity {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0 8px;
    min-width: 0;

    small {
      flex-basis: 100%;
      font-size: var(--fs-xs);
      color: var(--muted);
      overflow-wrap: anywhere;
    }
  }

  &__name {
    font-family: var(--font-serif);
    font-size: 1.15rem;
    line-height: 1.2;
    color: var(--ink);
    text-decoration: none;

    &:focus-visible {
      outline: none;
    }
  }

  &__row:has(.accounts-table__name:focus-visible),
  &__card:has(.accounts-table__name:focus-visible) {
    outline: 2px solid var(--saffron);
    outline-offset: -2px;
  }

  // Compte désactivé : ligne atténuée (portrait pâli, nom et dates en gris), statut en terracotta
  &__row--inactive {
    :deep(.member-avatar) {
      opacity: 0.5;
      filter: grayscale(1);
    }

    .accounts-table__name {
      color: var(--muted);
    }

    :deep(td),
    .accounts-table__last {
      color: var(--muted);
    }
  }

  &__you {
    font-size: 11px;
    font-weight: 600;
    color: var(--petrol);
  }

  // Rôles et cadenas du superadmin sur une même ligne
  &__roles {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
  }

  &__protected {
    display: inline-flex;
    align-items: center;
    color: var(--muted);
  }

  // Invitation : portrait à initiales cerné de pointillés (compte pas encore activé)
  :deep(.accounts-table__avatar--invited) {
    background: transparent;
    box-shadow: inset 0 0 0 1.5px var(--border-hover);
    outline: 1.5px dashed var(--muted);
    outline-offset: -1.5px;
  }

  // Téléphone : cartes empilées
  :deep(.accounts-table__cards) {
    display: grid;
    gap: 10px;
  }

  &__card {
    display: grid;
    gap: 10px;
    padding: 14px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    transition: border-color 0.15s;

    &:hover {
      border-color: var(--card-hover-border);
    }
  }

  &__email {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__card-foot {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }

  &__last {
    font-size: var(--fs-xs);
    text-align: right;
    color: var(--ink-2);

    span {
      display: block;
      font-size: 11px;
      color: var(--muted);
    }
  }
}

// Téléphone (mode grid, comme $q.screen.lt.sm) : cartes sans cadre de tableau autour
@media (max-width: 719px) {
  .accounts-table {
    background: none;
    border: 0;
  }
}
</style>
