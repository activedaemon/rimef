<script setup lang="ts">
// Fenêtre « Inviter une personne » (maquette Administration) : prénom, nom, email. Une
// administratrice invite toujours un compte membre ; le superadmin coche les rôles. Erreurs de
// l'API par champ (email déjà utilisé : lien vers le compte). Après l'envoi : état
// « Invitation envoyée », avec « Inviter une autre personne ». Feuille en bas sur téléphone.
import { isAxiosError } from 'axios';
import { useQuasar } from 'quasar';
import { reactive, ref, watch } from 'vue';

import AppButton from '@/components/AppButton.vue';
import { extractApiError } from '@/lib/http';
import { useSession } from '@/stores/session';
import {
  inviteAccount,
  longDate,
  type Account,
  type AccountRole,
  type InvitationPayload,
} from '../services/accounts';

const INVITATION_DAYS = 7;

const open = defineModel<boolean>({ required: true });
const emit = defineEmits<{ invited: [account: Account] }>();

const $q = useQuasar();
const session = useSession();

type Field = 'first_name' | 'last_name' | 'email' | 'roles';

const form = reactive({ first_name: '', last_name: '', email: '', member: true, admin: false });
const errors = ref<Partial<Record<Field, string>>>({});
const generalError = ref('');
const sending = ref(false);
const invited = ref<Account | null>(null);

function reset(): void {
  Object.assign(form, { first_name: '', last_name: '', email: '', member: true, admin: false });
  errors.value = {};
  generalError.value = '';
  invited.value = null;
}

watch(open, (value) => {
  if (value) reset();
});

function validate(): boolean {
  const found: Partial<Record<Field, string>> = {};
  if (!form.first_name.trim()) found.first_name = 'Indiquez son prénom.';
  if (!form.last_name.trim()) found.last_name = 'Indiquez son nom.';
  if (!form.email.trim()) found.email = 'Indiquez son adresse email.';
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
    found.email = 'Cette adresse n’est pas valide, par exemple nom@exemple.org.';
  }
  if (session.isSuperAdmin && !form.member && !form.admin)
    found.roles = 'Choisissez au moins un rôle.';
  errors.value = found;
  return Object.keys(found).length === 0;
}

async function submit(): Promise<void> {
  if (sending.value || !validate()) return;
  const payload: InvitationPayload = {
    first_name: form.first_name.trim(),
    last_name: form.last_name.trim(),
    email: form.email.trim(),
  };
  if (session.isSuperAdmin) {
    payload.roles = [
      ...(form.member ? (['member'] as AccountRole[]) : []),
      ...(form.admin ? (['admin'] as AccountRole[]) : []),
    ];
  }
  sending.value = true;
  generalError.value = '';
  try {
    invited.value = await inviteAccount(payload);
    emit('invited', invited.value);
  } catch (error) {
    if (isAxiosError(error) && error.response?.status === 422) {
      const apiErrors = (error.response.data as { errors?: Record<string, string[]> }).errors ?? {};
      errors.value = Object.fromEntries(
        Object.entries(apiErrors).map(([key, messages]) => [key.split('.')[0], messages[0] ?? ''])
      );
    } else {
      generalError.value = extractApiError(
        error,
        'L’invitation n’a pas pu être envoyée. Réessayez.'
      );
    }
  } finally {
    sending.value = false;
  }
}
</script>

<template>
  <q-dialog v-model="open" :position="$q.screen.lt.sm ? 'bottom' : 'standard'">
    <q-card class="message-dialog invite-dialog" role="dialog" aria-labelledby="invite-title">
      <div class="message-dialog__head">
        <span class="invite-dialog__icon" :class="{ 'invite-dialog__icon--done': invited }">
          <q-icon :name="invited ? 'check' : 'user-plus'" size="18px" aria-hidden="true" />
        </span>
        <div>
          <h2 id="invite-title">{{ invited ? 'Invitation envoyée' : 'Inviter une personne' }}</h2>
          <p v-if="!invited">Le compte sera créé à l’activation du lien</p>
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

      <template v-if="invited">
        <p class="invite-dialog__done" role="status">
          <b>{{ invited.name }}</b> a reçu un email à l’adresse {{ invited.email }}. Le lien
          d’activation est valable jusqu’au
          {{ invited.invitation_expires_at ? longDate(invited.invitation_expires_at) : '' }}. Son
          compte apparaît dans la liste avec le statut « Invitation en attente ».
        </p>
        <div class="invite-dialog__foot">
          <AppButton v-close-popup variant="quiet" label="Fermer" />
          <AppButton label="Inviter une autre personne" @click="reset" />
        </div>
      </template>

      <form v-else novalidate @submit.prevent="submit">
        <p v-if="generalError" class="invite-dialog__alert" role="alert">{{ generalError }}</p>

        <div class="invite-dialog__names">
          <div>
            <label class="field-label" for="invite-first-name">Prénom</label>
            <q-input
              v-model="form.first_name"
              for="invite-first-name"
              outlined
              dense
              autocomplete="off"
              :error="!!errors.first_name"
              :error-message="errors.first_name"
              maxlength="100"
            />
          </div>
          <div>
            <label class="field-label" for="invite-last-name">Nom</label>
            <q-input
              v-model="form.last_name"
              for="invite-last-name"
              outlined
              dense
              autocomplete="off"
              :error="!!errors.last_name"
              :error-message="errors.last_name"
              maxlength="100"
            />
          </div>
        </div>

        <label class="field-label" for="invite-email">Email</label>
        <q-input
          v-model="form.email"
          for="invite-email"
          type="email"
          outlined
          dense
          autocomplete="off"
          :error="!!errors.email"
          :error-message="errors.email"
          maxlength="255"
        />

        <fieldset v-if="session.isSuperAdmin" class="invite-dialog__roles">
          <legend class="field-label">Rôles</legend>
          <q-checkbox v-model="form.member" class="invite-dialog__role">
            <span
              ><b>Membre</b><small>Fiche dans le réseau et présence dans l’annuaire.</small></span
            >
          </q-checkbox>
          <q-checkbox v-model="form.admin" class="invite-dialog__role">
            <span><b>Administratrice</b><small>Accès à la rubrique Administration.</small></span>
          </q-checkbox>
          <p v-if="errors.roles" class="invite-dialog__error" role="alert">{{ errors.roles }}</p>
        </fieldset>
        <p v-else class="invite-dialog__note">
          Cette personne rejoindra le réseau comme <b>membre</b> et complétera elle-même sa fiche.
        </p>

        <div class="invite-dialog__foot invite-dialog__foot--form">
          <small>
            Un email lui sera envoyé avec un lien pour choisir son mot de passe. Ce lien est valable
            {{ INVITATION_DAYS }} jours.
          </small>
          <div class="invite-dialog__buttons">
            <AppButton v-close-popup variant="quiet" label="Annuler" />
            <AppButton type="submit" label="Envoyer l’invitation" :loading="sending" />
          </div>
        </div>
      </form>
    </q-card>
  </q-dialog>
</template>

<style lang="scss" scoped>
.invite-dialog {
  &__icon {
    display: grid;
    place-items: center;
    flex: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--petrol-light);
    color: var(--petrol);

    &--done {
      background: var(--sage-light);
      color: var(--sage-ink);
    }
  }

  &__names {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 14px;
  }

  &__roles {
    display: grid;
    margin: var(--s-2) 0 0;
    padding: 0;
    border: 0;

    legend {
      padding: 0;
    }
  }

  &__role {
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

  &__note {
    margin-top: var(--s-2);
    padding: 10px 12px;
    border-radius: var(--radius-xs);
    background: var(--ivory);
    font-size: var(--fs-sm);
    color: var(--ink-2);
  }

  &__alert,
  &__error {
    font-size: var(--fs-sm);
    color: var(--terracotta-ink);
  }

  &__alert {
    margin-bottom: var(--s-3);
  }

  &__done {
    font-size: var(--fs-sm);
    line-height: 1.65;
    color: var(--ink-2);

    b {
      color: var(--ink);
    }
  }

  &__foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: var(--s-3);
    margin-top: var(--s-5);

    small {
      flex: 1 1 220px;
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }

  &__buttons {
    display: flex;
    gap: 10px;
  }
}

@media (max-width: 719px) {
  .invite-dialog__names {
    grid-template-columns: 1fr;
  }

  .invite-dialog__buttons,
  .invite-dialog__foot > .q-btn {
    flex: 1;
  }

  .invite-dialog__buttons .q-btn {
    flex: 1;
  }
}
</style>
