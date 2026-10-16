<script setup lang="ts">
// Statut d'un compte (fiche StatusBadge) : pastille colorée + repère + mot, jamais la couleur
// seule. Actif en sauge, Désactivé en terracotta (icône « interdit »), invitation en attente en
// safran, invitation expirée sur fond papier (horloge barrée). Contrastes AA. Emplacement par
// défaut : complément (ex. info-bulle de l'échéance d'une invitation).
import { STATUS_LABELS, type AccountStatus } from '../services/accounts';

defineProps<{ status: AccountStatus }>();

const ICONS: Partial<Record<AccountStatus, string>> = { inactive: 'ban', expired: 'clock-off' };
</script>

<template>
  <span class="account-status" :class="`account-status--${status}`">
    <q-icon v-if="ICONS[status]" :name="ICONS[status]" size="14px" aria-hidden="true" />
    <span v-else class="account-status__dot" aria-hidden="true" />
    {{ STATUS_LABELS[status] }}
    <slot />
  </span>
</template>

<style lang="scss" scoped>
.account-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 24px;
  padding: 2px 10px 2px 8px;
  border-radius: var(--radius-pill);
  font-size: var(--fs-xs);
  font-weight: 600;
  line-height: 1.3;
  white-space: nowrap;

  &__dot {
    flex: none;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
  }

  // 5,8:1
  &--active {
    background: var(--sage-light);
    color: var(--sage-ink);

    .account-status__dot {
      background: var(--sage);
    }
  }

  // 4,6:1
  &--inactive {
    background: var(--terracotta-light);
    color: var(--terracotta-ink);
  }

  // Encre safran foncée d'un cran (#7a5d1c, 5,2:1) : celle des jetons reste sous 4,5:1
  &--pending {
    background: var(--saffron-light);
    color: #7a5d1c;

    .account-status__dot {
      background: var(--saffron);
    }
  }

  &--expired {
    background: var(--paper);
    box-shadow: inset 0 0 0 1px var(--border-hover);
    color: var(--ink-2);
  }
}
</style>
