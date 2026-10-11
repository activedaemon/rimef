<script setup lang="ts">
// Premier message d'une prise de contact, écrit dans la messagerie du réseau. Partagé par les
// fenêtres « Contacter » (fiche) et « Nouveau message ». Le formulaire est vide à chaque
// ouverture de la fenêtre qui le contient (monté avec elle).
// Emplacement « secondary » : action à gauche du bouton d'envoi (ex. changer de destinataire).
import { computed, ref } from 'vue';

import AppButton from '@/components/AppButton.vue';
import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import { MAX_MESSAGE_LENGTH, contactMember } from '../services/messages';

const props = defineProps<{ recipient: { slug: string; first_name: string } }>();
const emit = defineEmits<{ sent: [conversationId: number] }>();

const notify = useNotify();

const body = ref('');
const sending = ref(false);

const canSend = computed(() => body.value.trim() !== '' && !sending.value);

async function submit(): Promise<void> {
  if (!canSend.value) return;
  sending.value = true;
  try {
    const conversationId = await contactMember(props.recipient.slug, body.value.trim());
    notify.success(`Message envoyé à ${props.recipient.first_name}.`);
    emit('sent', conversationId);
  } catch (error) {
    notify.error(extractApiError(error, 'Le message n’a pas pu être envoyé. Réessayez.'));
  } finally {
    sending.value = false;
  }
}

/** Champ du message, pour y placer le focus à l'ouverture. */
const bodyInput = ref<{ focus: () => void } | null>(null);
defineExpose({ focus: () => bodyInput.value?.focus() });
</script>

<template>
  <form class="contact-form" @submit.prevent="submit">
    <label class="field-label" for="contact-body">Message</label>
    <q-input
      ref="bodyInput"
      v-model="body"
      for="contact-body"
      type="textarea"
      outlined
      autogrow
      :maxlength="MAX_MESSAGE_LENGTH"
      counter
      :placeholder="`Bonjour ${recipient.first_name}, je prépare… et j’aimerais bénéficier de votre regard sur…`"
      class="contact-form__field contact-form__message"
    />

    <div class="contact-form__foot">
      <slot name="secondary" />
      <AppButton
        type="submit"
        label="Envoyer le message"
        :loading="sending"
        :disable="!canSend"
        class="contact-form__send"
      />
    </div>
  </form>
</template>

<style lang="scss" scoped>
.contact-form {
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

  &__send {
    margin-left: auto;
  }
}

// Téléphone : bouton d'envoi sur toute la largeur
@media (max-width: 719px) {
  .contact-form__send {
    width: 100%;
  }
}
</style>
