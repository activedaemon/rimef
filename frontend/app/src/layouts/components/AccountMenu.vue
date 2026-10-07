<script setup lang="ts">
// Menu du compte (fiche AccountMenu) : identité, profil, déconnexion.
// Flèches haut/bas entre les entrées ; Échap ferme et rend le focus au bouton (q-menu).
import { computed, ref } from 'vue';

import { useLogout } from '@/composables/useLogout';
import { useSession } from '@/stores/session';

const session = useSession();
const { logout, loggingOut } = useLogout();
const menuList = ref<{ $el: HTMLElement } | null>(null);

const roleLabel = computed(() => (session.isAdmin ? 'Administratrice' : 'Membre RIMEF'));

function focusFirstItem(): void {
  menuList.value?.$el.querySelector<HTMLElement>('.q-item')?.focus();
}

function moveFocus(event: KeyboardEvent): void {
  const items = Array.from(menuList.value?.$el.querySelectorAll<HTMLElement>('.q-item') ?? []);
  const current = items.indexOf(document.activeElement as HTMLElement);
  const step = event.key === 'ArrowDown' ? 1 : -1;
  items[(current + step + items.length) % items.length]?.focus();
}
</script>

<template>
  <q-btn
    flat
    round
    class="account-button"
    :aria-label="`Mon compte, ${session.user?.name ?? ''}`"
    aria-haspopup="menu"
  >
    <q-avatar size="38px" class="account-button__avatar">{{ session.initials }}</q-avatar>

    <q-menu
      anchor="bottom right"
      self="top right"
      :offset="[0, 8]"
      class="account-menu"
      @show="focusFirstItem"
    >
      <div class="account-menu__identity">
        <q-avatar size="40px" class="account-button__avatar">{{ session.initials }}</q-avatar>
        <div>
          <p class="account-menu__name">{{ session.user?.name }}</p>
          <p class="account-menu__role">{{ roleLabel }}</p>
        </div>
      </div>

      <q-list
        ref="menuList"
        role="menu"
        class="account-menu__list"
        @keydown.down.prevent="moveFocus"
        @keydown.up.prevent="moveFocus"
      >
        <q-item v-close-popup clickable role="menuitem" :to="{ name: 'profile' }">
          <q-item-section avatar><q-icon name="user" size="16px" /></q-item-section>
          <q-item-section>Voir mon profil</q-item-section>
        </q-item>
        <q-item v-close-popup clickable role="menuitem" :to="{ name: 'profile-edit' }">
          <q-item-section avatar><q-icon name="pencil" size="16px" /></q-item-section>
          <q-item-section>Modifier mon profil</q-item-section>
        </q-item>
        <q-separator class="account-menu__separator" />
        <q-item v-close-popup clickable role="menuitem" :disable="loggingOut" @click="logout">
          <q-item-section avatar><q-icon name="logout" size="16px" /></q-item-section>
          <q-item-section>Se déconnecter</q-item-section>
        </q-item>
      </q-list>
    </q-menu>
  </q-btn>
</template>

<style lang="scss" scoped>
.account-button__avatar {
  background: var(--petrol-light);
  color: var(--petrol);
  font-size: var(--fs-sm);
  font-weight: 600;
  box-shadow:
    0 0 0 2px var(--paper),
    0 0 0 3px var(--border);
}
</style>

<style lang="scss">
// q-menu est rendu hors du composant : styles non scopés, préfixés .account-menu
.account-menu {
  width: 250px;
  padding: var(--s-2);
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-float);

  &__identity {
    display: flex;
    align-items: center;
    gap: var(--s-3);
    padding: var(--s-2) var(--s-2) var(--s-3);
  }

  &__name {
    font-weight: 600;
    color: var(--ink);
  }

  &__role {
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &__list .q-item {
    min-height: 44px;
    padding: 0 var(--s-2);
    border-radius: var(--radius-xs);
    font-size: var(--fs-sm);
    color: var(--ink-2);

    .q-item__section--avatar {
      min-width: 28px;
      color: var(--muted);
    }

    &.q-router-link--exact-active {
      color: var(--petrol);
      font-weight: 600;
    }
  }

  &__separator {
    margin: var(--s-1);
    background: var(--border);
  }
}
</style>
