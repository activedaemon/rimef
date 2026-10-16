<script setup lang="ts">
// Entrées du compte, communes au menu du compte et au tiroir mobile : profil (son édition
// sans rôle member), messages (avec les non-lus), Administration (administratrices et
// superadmin), déconnexion. À placer dans un q-list ; v-close-popup referme le menu
// (sans effet dans le tiroir, qui n'est pas un popup).
import { computed } from 'vue';

import { useLogout } from '@/composables/useLogout';
import { useInbox } from '@/stores/inbox';
import { useSession } from '@/stores/session';

interface AccountLink {
  label: string;
  routeName: string;
  /** Icône Tabler (sans le préfixe ti-). */
  icon: string;
  /** Pastille de non-lus (Mes messages). */
  unread?: number;
}

defineProps<{
  iconSize: string;
  /** Rôle ARIA des entrées (menuitem dans le menu du compte). */
  itemRole?: string;
}>();
const emit = defineEmits<{ navigate: [] }>();

const session = useSession();
const inbox = useInbox();
const { logout, loggingOut } = useLogout();

const links = computed<AccountLink[]>(() => [
  session.isMember
    ? { label: 'Voir mon profil', routeName: 'profile', icon: 'user' }
    : { label: 'Modifier mon profil', routeName: 'profile-edit', icon: 'pencil' },
  { label: 'Mes messages', routeName: 'messages', icon: 'message', unread: inbox.unreadMessages },
  ...(session.isAdmin ? [{ label: 'Administration', routeName: 'admin', icon: 'shield-check' }] : []),
]);
</script>

<template>
  <!-- active-class neutralisé : seule la page exacte est mise en avant -->
  <q-item
    v-for="link in links"
    :key="link.routeName"
    v-close-popup
    clickable
    :role="itemRole"
    :to="{ name: link.routeName }"
    active-class=""
    class="account-links__item"
    @click="emit('navigate')"
  >
    <q-item-section avatar><q-icon :name="link.icon" :size="iconSize" /></q-item-section>
    <q-item-section>{{ link.label }}</q-item-section>
    <q-item-section v-if="link.unread" side>
      <q-badge
        class="rimef-badge account-links__count"
        :label="link.unread"
        :aria-label="`${link.unread} non lus`"
      />
    </q-item-section>
  </q-item>
  <q-separator class="account-links__separator" />
  <q-item
    v-close-popup
    clickable
    :role="itemRole"
    :disable="loggingOut"
    class="account-links__item"
    @click="logout"
  >
    <q-item-section avatar><q-icon name="logout" :size="iconSize" /></q-item-section>
    <q-item-section>Se déconnecter</q-item-section>
  </q-item>
</template>

<style lang="scss" scoped>
.account-links {
  // .q-item en plus : passe devant la couleur des entrées posée par le menu et le tiroir
  &__item.q-item.q-router-link--exact-active {
    color: var(--petrol);
    font-weight: 600;
  }

  // Messages non lus
  &__count.q-badge {
    background: var(--terracotta);
    color: var(--paper);
  }

  &__separator {
    margin: var(--s-1);
    background: var(--border);
  }
}
</style>
