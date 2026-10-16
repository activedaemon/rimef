<script setup lang="ts">
// Menu du compte (fiche AccountMenu) : identité, puis les entrées du compte (AccountLinks).
// Pastille : photo de la personne connectée si elle en a une, sinon ses initiales.
// Flèches haut/bas entre les entrées ; Échap ferme et rend le focus au bouton (q-menu).
import { ref } from 'vue';

import MemberAvatar from '@/components/MemberAvatar.vue';
import { useSession } from '@/stores/session';
import AccountLinks from './AccountLinks.vue';

const session = useSession();
const menuList = ref<{ $el: HTMLElement } | null>(null);

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
    <MemberAvatar
      :name="session.user?.name ?? ''"
      :photo="session.user?.photo_url ?? null"
      :size="38"
      class="account-button__avatar"
    />

    <q-menu
      anchor="bottom right"
      self="top right"
      :offset="[0, 8]"
      class="account-menu"
      @show="focusFirstItem"
    >
      <div class="account-menu__identity">
        <MemberAvatar
          :name="session.user?.name ?? ''"
          :photo="session.user?.photo_url ?? null"
          :size="40"
          class="account-button__avatar"
        />
        <div>
          <p class="account-menu__name">{{ session.user?.name }}</p>
          <p class="account-menu__role">{{ session.roleLabel }}</p>
        </div>
      </div>

      <q-list
        ref="menuList"
        role="menu"
        class="account-menu__list"
        @keydown.down.prevent="moveFocus"
        @keydown.up.prevent="moveFocus"
      >
        <AccountLinks icon-size="16px" item-role="menuitem" />
      </q-list>
    </q-menu>
  </q-btn>
</template>

<style lang="scss" scoped>
.account-button__avatar {
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
  }
}
</style>
