<script setup lang="ts">
// Fenêtre « Contacter » d'une fiche, ou de l'enveloppe d'une médiatrice sur la fiche d'un événement
// (maquette) : message écrit dans la messagerie du réseau ; la médiatrice est prévenue par email.
// Feuille en bas sur téléphone. Émet `sent` avec la conversation où le message a été écrit.
import { useQuasar } from 'quasar';

import MemberAvatar from '@/components/MemberAvatar.vue';
import ContactForm from '@modules/messages/components/ContactForm.vue';

/** Médiatrice écrite : fiche de l'annuaire ou médiatrice présente à un événement. */
export interface ContactRecipient {
  slug: string;
  name: string;
  first_name: string;
  photo_url: string | null;
}

defineProps<{ profile: ContactRecipient }>();
const open = defineModel<boolean>({ required: true });
const emit = defineEmits<{ sent: [conversationId: number] }>();

const $q = useQuasar();

function onSent(conversationId: number): void {
  open.value = false;
  emit('sent', conversationId);
}
</script>

<template>
  <q-dialog v-model="open" :position="$q.screen.lt.sm ? 'bottom' : 'standard'">
    <q-card class="message-dialog" role="dialog" aria-labelledby="contact-title">
      <div class="message-dialog__head">
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
          class="message-dialog__close"
        />
      </div>

      <ContactForm :recipient="profile" @sent="onSent" />
    </q-card>
  </q-dialog>
</template>
