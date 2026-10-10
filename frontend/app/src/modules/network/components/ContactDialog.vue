<script setup lang="ts">
// Fenêtre « Contacter » d'une fiche (maquette) : objet de la demande et message, écrits dans
// la messagerie du réseau ; la médiatrice est prévenue par email. Feuille en bas sur téléphone.
import { useQuasar } from 'quasar';
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

import AppButton from '@/components/AppButton.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import {
  CONTACT_SUBJECTS,
  MAX_MESSAGE_LENGTH,
  contactMember,
  type ContactSubject,
} from '@modules/messages/services/messages';
import type { MemberProfile } from '../services/members';

const props = defineProps<{ profile: MemberProfile }>();
const open = defineModel<boolean>({ required: true });

const $q = useQuasar();
const router = useRouter();
const notify = useNotify();

const subject = ref<ContactSubject>('expertise');
const body = ref('');
const sending = ref(false);

const canSend = computed(() => body.value.trim() !== '' && !sending.value);

// Nouvelle fenêtre : formulaire vide
watch(open, (value) => {
  if (value) {
    subject.value = 'expertise';
    body.value = '';
  }
});

async function submit(): Promise<void> {
  if (!canSend.value) return;
  sending.value = true;
  try {
    const conversationId = await contactMember(
      props.profile.slug,
      subject.value,
      body.value.trim()
    );
    open.value = false;
    notify.success(`Message envoyé à ${props.profile.first_name}.`, {
      label: 'Voir la conversation',
      handler: () => void router.push({ name: 'conversation', params: { id: conversationId } }),
    });
  } catch (error) {
    notify.error(extractApiError(error, 'Le message n’a pas pu être envoyé. Réessayez.'));
  } finally {
    sending.value = false;
  }
}
</script>

<template>
  <q-dialog v-model="open" :position="$q.screen.lt.sm ? 'bottom' : 'standard'">
    <q-card class="contact-dialog" role="dialog" aria-labelledby="contact-title">
      <form @submit.prevent="submit">
        <div class="contact-dialog__head">
          <MemberAvatar :name="profile.name" :photo="profile.photo_url" :size="48" />
          <div>
            <h2 id="contact-title">Contacter {{ profile.first_name }}</h2>
            <p>Message transmis via la messagerie du réseau</p>
          </div>
          <q-btn
            v-close-popup
            flat
            round
            icon="x"
            aria-label="Fermer"
            class="contact-dialog__close"
          />
        </div>

        <label class="field-label" for="contact-subject">Objet de la demande</label>
        <q-select
          v-model="subject"
          for="contact-subject"
          :options="CONTACT_SUBJECTS"
          emit-value
          map-options
          outlined
          dense
          options-dense
          class="contact-dialog__field"
        />

        <label class="field-label" for="contact-body">Message</label>
        <q-input
          v-model="body"
          for="contact-body"
          type="textarea"
          outlined
          autogrow
          :maxlength="MAX_MESSAGE_LENGTH"
          counter
          :placeholder="`Bonjour ${profile.first_name}, je prépare… et j’aimerais bénéficier de votre regard sur…`"
          class="contact-dialog__field contact-dialog__message"
        />

        <div class="contact-dialog__foot">
          <AppButton
            type="submit"
            label="Envoyer le message"
            :loading="sending"
            :disable="!canSend"
          />
        </div>
      </form>
    </q-card>
  </q-dialog>
</template>

<style lang="scss" scoped>
.contact-dialog {
  width: min(520px, calc(100vw - 32px));
  max-width: none;
  padding: 26px 28px 24px;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-float);

  &__head {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: var(--s-5);

    h2 {
      font-size: 1.6rem;
      line-height: 1.15;
    }

    p {
      font-size: var(--fs-sm);
      color: var(--muted);
    }
  }

  &__close {
    align-self: flex-start;
    margin-left: auto;
  }

  &__field {
    margin: 6px 0 var(--s-4);
  }

  &__message :deep(textarea) {
    min-height: 128px;
    line-height: 1.6;
  }

  &__foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: var(--s-2);
  }
}

// Téléphone : feuille en bas de l'écran, sur toute la largeur
@media (max-width: 719px) {
  .contact-dialog {
    width: 100vw;
    padding: 22px 16px calc(20px + env(safe-area-inset-bottom, 0px));
    border-radius: var(--radius-md) var(--radius-md) 0 0;

    &__foot :deep(.app-btn) {
      width: 100%;
    }
  }
}
</style>
