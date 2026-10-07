<script setup lang="ts">
import { ref } from 'vue';

import { extractApiError } from '@/lib/http';
import { forgotPassword } from '../services/auth';

const email = ref('');
const submitting = ref(false);
const error = ref('');
const confirmation = ref('');

const emailRules = [
  (value: string) => !!value || 'Indiquez votre adresse email.',
  (value: string) =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Cette adresse email n’est pas valide.',
];

async function submit(): Promise<void> {
  submitting.value = true;
  error.value = '';
  try {
    confirmation.value = await forgotPassword(email.value);
  } catch (exception) {
    error.value = extractApiError(exception, 'L’envoi a échoué. Réessayez dans un instant.');
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <q-form class="auth-form" novalidate @submit="submit">
    <header class="auth-form__header">
      <h1 class="auth-form__title">Mot de passe oublié</h1>
      <p class="auth-form__lead">
        Indiquez l’adresse email de votre compte : vous recevrez un lien pour choisir un nouveau mot
        de passe.
      </p>
    </header>

    <p v-if="confirmation" class="auth-form__notice" role="status">{{ confirmation }}</p>
    <p v-if="error" class="auth-form__alert" role="alert">{{ error }}</p>

    <template v-if="!confirmation">
      <div>
        <label for="forgot-email" class="field-label">Adresse email</label>
        <q-input
          v-model.trim="email"
          for="forgot-email"
          type="email"
          autocomplete="email"
          outlined
          dense
          hide-bottom-space
          lazy-rules
          :rules="emailRules"
        />
      </div>

      <q-btn
        type="submit"
        color="primary"
        unelevated
        no-caps
        label="Recevoir le lien"
        class="auth-form__submit"
        :loading="submitting"
      />
    </template>

    <nav class="auth-form__links" aria-label="Retour">
      <router-link :to="{ name: 'login' }" class="auth-form__link">
        Retour à la connexion
      </router-link>
    </nav>
  </q-form>
</template>

<style lang="scss" scoped>
@use './auth-form';
</style>
