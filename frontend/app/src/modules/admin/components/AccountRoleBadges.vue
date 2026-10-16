<script setup lang="ts">
// Rôles d'un compte en badges : Superadministrateur et Administratrice en pétrole clair, Membre
// en sauge.
import { computed } from 'vue';

import { ROLE_LABELS, sortedRoles, type AccountRole } from '../services/accounts';

const props = defineProps<{ roles: AccountRole[] }>();

const badges = computed(() => sortedRoles(props.roles));
</script>

<template>
  <span class="account-roles">
    <q-badge
      v-for="role in badges"
      :key="role"
      class="rimef-badge account-roles__badge"
      :class="`account-roles__badge--${role === 'member' ? 'member' : 'team'}`"
      :label="ROLE_LABELS[role]"
    />
  </span>
</template>

<style lang="scss" scoped>
.account-roles {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 6px;

  &__badge--member.q-badge {
    background: var(--sage-light);
    color: var(--sage-ink);
  }

  &__badge--team.q-badge {
    background: var(--petrol-light);
    color: var(--petrol);
  }
}
</style>
