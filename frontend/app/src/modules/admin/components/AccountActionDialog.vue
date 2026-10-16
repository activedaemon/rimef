<script setup lang="ts">
// Confirmation d'une action sur un compte (maquette Administration) : désactiver, réactiver,
// annuler une invitation, modifier les rôles (rappel des conséquences) ou supprimer
// définitivement (récapitulatif Effacé / Gardé, nom complet à saisir). Émet `done` avec le
// compte mis à jour, ou null s'il n'existe plus (invitation annulée, compte supprimé).
import { useQuasar } from 'quasar';
import { computed, ref, watch } from 'vue';

import AppButton from '@/components/AppButton.vue';
import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import {
  cancelInvitation,
  deleteAccount,
  setActivation,
  updateRoles,
  type Account,
  type AccountRole,
} from '../services/accounts';

export type AccountAction = 'deactivate' | 'reactivate' | 'cancel' | 'roles' | 'delete';

const props = defineProps<{
  account: Account;
  action: AccountAction;
  /** Rôles choisis (action « roles »). */
  nextRoles?: AccountRole[];
}>();
const open = defineModel<boolean>({ required: true });
const emit = defineEmits<{ done: [account: Account | null] }>();

const $q = useQuasar();
const notify = useNotify();

const confirmation = ref('');
const busy = ref(false);

watch(open, (value) => {
  if (value) confirmation.value = '';
});

function normalize(text: string): string {
  return text.normalize('NFD').replace(/[̀-ͯ]/g, '').trim().toLowerCase();
}

const roleConsequences = computed(() => {
  const next = props.nextRoles ?? [];
  const had = (role: AccountRole) => props.account.roles.includes(role);
  const will = (role: AccountRole) => next.includes(role);
  const lines: string[] = [];
  if (will('admin') && !had('admin'))
    lines.push('Ce compte aura accès à la rubrique Administration.');
  if (!will('admin') && had('admin'))
    lines.push('Ce compte n’aura plus accès à la rubrique Administration.');
  if (will('member') && !had('member')) {
    lines.push('Une fiche apparaîtra dans l’annuaire ; la personne la complétera elle-même.');
  }
  if (!will('member') && had('member')) {
    lines.push(
      'Ce compte n’apparaîtra plus dans l’annuaire et ne pourra plus participer aux événements.'
    );
  }
  return lines;
});

const content = computed(() => {
  const first = props.account.first_name;
  switch (props.action) {
    case 'deactivate':
      return {
        icon: 'player-pause',
        title: `Désactiver le compte de ${first} ?`,
        confirm: 'Désactiver le compte',
      };
    case 'reactivate':
      return {
        icon: 'player-play',
        title: `Réactiver le compte de ${first} ?`,
        confirm: 'Réactiver le compte',
      };
    case 'cancel':
      return { icon: 'x', title: 'Annuler l’invitation ?', confirm: 'Annuler l’invitation' };
    case 'roles':
      return {
        icon: 'shield-check',
        title: `Modifier les rôles de ${first} ?`,
        confirm: 'Confirmer',
      };
    default:
      return {
        icon: 'trash',
        title: 'Supprimer définitivement ce compte ?',
        confirm: 'Supprimer définitivement',
      };
  }
});

const canConfirm = computed(
  () =>
    !busy.value &&
    (props.action !== 'delete' || normalize(confirmation.value) === normalize(props.account.name))
);

async function confirm(): Promise<void> {
  if (!canConfirm.value) return;
  busy.value = true;
  const id = props.account.id;
  try {
    let result: Account | null = null;
    let message = '';
    switch (props.action) {
      case 'deactivate':
        result = await setActivation(id, false);
        message = 'Compte désactivé';
        break;
      case 'reactivate':
        result = await setActivation(id, true);
        message = 'Compte réactivé';
        break;
      case 'cancel':
        await cancelInvitation(id);
        message = 'Invitation annulée';
        break;
      case 'roles':
        result = await updateRoles(id, props.nextRoles ?? []);
        message = 'Rôles enregistrés';
        break;
      case 'delete':
        await deleteAccount(id);
        message = 'Compte supprimé';
        break;
    }
    notify.success(message);
    open.value = false;
    emit('done', result);
  } catch (error) {
    notify.error(extractApiError(error, 'L’action n’a pas pu être enregistrée. Réessayez.'));
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <q-dialog v-model="open" :position="$q.screen.lt.sm ? 'bottom' : 'standard'">
    <q-card
      class="message-dialog account-action"
      role="dialog"
      aria-labelledby="account-action-title"
    >
      <div class="message-dialog__head">
        <span
          class="account-action__icon"
          :class="{ 'account-action__icon--danger': action === 'delete' }"
          aria-hidden="true"
        >
          <q-icon :name="content.icon" size="18px" />
        </span>
        <div>
          <h2 id="account-action-title">{{ content.title }}</h2>
          <p>
            {{ account.name }}<template v-if="action === 'cancel'"> · {{ account.email }}</template>
          </p>
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

      <form novalidate @submit.prevent="confirm">
        <ul v-if="action === 'deactivate'" class="account-action__list">
          <li>La connexion sera impossible et les sessions ouvertes seront fermées.</li>
          <li>Le compte disparaîtra de l’annuaire du réseau.</li>
          <li>Les conversations, participations et favoris sont conservés.</li>
          <li>Vous pourrez réactiver le compte à tout moment.</li>
        </ul>
        <p v-else-if="action === 'reactivate'" class="account-action__text">
          La personne pourra de nouveau se connecter avec son mot de passe<template
            v-if="account.roles.includes('member')"
          >
            et sa fiche réapparaîtra dans l’annuaire</template
          >.
        </p>
        <p v-else-if="action === 'cancel'" class="account-action__text">
          Le lien envoyé ne fonctionnera plus et le compte disparaîtra de la liste. Vous pourrez
          l’inviter de nouveau plus tard.
        </p>
        <ul v-else-if="action === 'roles'" class="account-action__list">
          <li v-for="line in roleConsequences" :key="line">{{ line }}</li>
        </ul>
        <template v-else>
          <p class="account-action__text">
            Cette action ne peut pas être annulée. Si la personne quitte simplement le réseau,
            désactivez plutôt son compte.
          </p>
          <div class="account-action__recap">
            <section>
              <h3>Effacé</h3>
              <ul>
                <li>Identité, email et photo</li>
                <li>Fiche, zones d’intervention, expertises et liens</li>
                <li>Favoris</li>
                <li>Participations aux événements</li>
              </ul>
            </section>
            <section>
              <h3>Gardé</h3>
              <ul>
                <li>Les chiffres, comme le nombre de participantes aux événements</li>
                <li>
                  Les messages déjà échangés, que l’autre personne conserve, signés « Compte
                  supprimé »
                </li>
              </ul>
            </section>
          </div>
          <label class="field-label account-action__label" for="account-action-name">
            Pour confirmer, saisissez son nom complet : <b>{{ account.name }}</b>
          </label>
          <q-input
            v-model="confirmation"
            for="account-action-name"
            outlined
            dense
            autocomplete="off"
            spellcheck="false"
          />
        </template>

        <div class="account-action__foot">
          <AppButton
            v-close-popup
            variant="quiet"
            :label="action === 'cancel' ? 'Garder l’invitation' : 'Annuler'"
          />
          <AppButton
            type="submit"
            :label="content.confirm"
            :loading="busy"
            :disable="!canConfirm"
            :class="{ 'account-action__danger': action === 'delete' }"
          />
        </div>
      </form>
    </q-card>
  </q-dialog>
</template>

<style lang="scss" scoped>
.account-action {
  &__icon {
    display: grid;
    place-items: center;
    flex: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--petrol-light);
    color: var(--petrol);

    &--danger {
      background: var(--terracotta-light);
      color: var(--terracotta-ink);
    }
  }

  &__list {
    display: grid;
    gap: 6px;
    margin: 0;
    padding-left: 18px;
    font-size: var(--fs-sm);
    line-height: 1.6;
    color: var(--ink-2);
  }

  &__text {
    font-size: var(--fs-sm);
    line-height: 1.65;
    color: var(--ink-2);
  }

  &__recap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: var(--s-4);

    section {
      padding: 12px 14px;
      border: 1px solid var(--border);
      border-radius: var(--radius-xs);
      background: var(--paper);
    }

    h3 {
      font-family: var(--font-ui);
      font-size: var(--fs-eyebrow);
      font-weight: 600;
      letter-spacing: 0.09em;
      text-transform: uppercase;
      color: var(--ink-2);
    }

    ul {
      display: grid;
      gap: 4px;
      margin: 8px 0 0;
      padding-left: 16px;
      font-size: var(--fs-xs);
      line-height: 1.5;
      color: var(--ink-2);
    }
  }

  &__label {
    display: block;
    margin-top: var(--s-4);

    b {
      color: var(--ink);
    }
  }

  &__foot {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: var(--s-5);
  }

  // Suppression définitive : terracotta foncé (seule action irréversible de la rubrique)
  &__danger.app-btn:not(.disabled) {
    background: var(--terracotta-ink);
    color: var(--paper);
  }
}

@media (max-width: 719px) {
  .account-action__recap {
    grid-template-columns: 1fr;
  }

  .account-action__foot .q-btn {
    flex: 1;
  }
}
</style>
