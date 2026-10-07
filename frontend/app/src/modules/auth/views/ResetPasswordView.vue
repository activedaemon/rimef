<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { extractApiError } from '@/lib/http';
import { resetPassword } from '../services/auth';

/** Longueur minimale, alignée sur Password::defaults() du backend. */
const PASSWORD_MIN_LENGTH = 12;

const route = useRoute();
const router = useRouter();

// Paramètres du lien reçu par email : ?token=…&email=…
const token = typeof route.query.token === 'string' ? route.query.token : '';
const email = typeof route.query.email === 'string' ? route.query.email : '';
const linkIsComplete = token !== '' && email !== '';

const form = reactive({ password: '', confirmation: '' });
const showPassword = ref(false);
const submitting = ref(false);
const error = ref('');

const passwordRules = [
  (value: string) =>
    value.length >= PASSWORD_MIN_LENGTH ||
    `Le mot de passe doit contenir au moins ${PASSWORD_MIN_LENGTH} caractères.`,
];
const confirmationRules = [
  (value: string) => value === form.password || 'Les deux mots de passe ne correspondent pas.',
];

async function submit(): Promise<void> {
  submitting.value = true;
  error.value = '';
  try {
    await resetPassword({
      token,
      email,
      password: form.password,
      password_confirmation: form.confirmation,
    });
    await router.replace({ name: 'login', query: { reinitialise: '1' } });
  } catch (exception) {
    error.value = extractApiError(exception, 'La réinitialisation a échoué. Réessayez.');
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <q-form class="auth-form" novalidate @submit="submit">
    <header class="auth-form__header">
      <h1 class="auth-form__title">Nouveau mot de passe</h1>
      <template v-if="linkIsComplete">
        <p class="auth-form__lead">Choisissez le nouveau mot de passe de votre compte.</p>
        <p class="auth-form__account">{{ email }}</p>
      </template>
    </header>

    <template v-if="linkIsComplete">
      <p v-if="error" class="auth-form__alert" role="alert">{{ error }}</p>

      <div>
        <label for="reset-password" class="field-label">Nouveau mot de passe</label>
        <q-input
          v-model="form.password"
          for="reset-password"
          :type="showPassword ? 'text' : 'password'"
          autocomplete="new-password"
          outlined
          dense
          hide-bottom-space
          lazy-rules
          :rules="passwordRules"
          :hint="`Au moins ${PASSWORD_MIN_LENGTH} caractères.`"
        >
          <template #append>
            <q-btn
              flat
              round
              dense
              :icon="showPassword ? 'eye-off' : 'eye'"
              :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
              :aria-pressed="showPassword"
              @click="showPassword = !showPassword"
            />
          </template>
        </q-input>
      </div>

      <div>
        <label for="reset-confirmation" class="field-label">Confirmez le mot de passe</label>
        <q-input
          v-model="form.confirmation"
          for="reset-confirmation"
          :type="showPassword ? 'text' : 'password'"
          autocomplete="new-password"
          outlined
          dense
          hide-bottom-space
          lazy-rules
          :rules="confirmationRules"
        />
      </div>

      <q-btn
        type="submit"
        color="primary"
        unelevated
        no-caps
        label="Enregistrer le mot de passe"
        class="auth-form__submit"
        :loading="submitting"
      />
    </template>

    <p v-else class="auth-form__alert" role="alert">
      Ce lien est incomplet ou a été mal copié. Demandez un nouveau lien de réinitialisation.
    </p>

    <nav class="auth-form__links" aria-label="Retour">
      <router-link
        :to="{ name: linkIsComplete ? 'login' : 'forgot-password' }"
        class="auth-form__link"
      >
        {{ linkIsComplete ? 'Retour à la connexion' : 'Demander un nouveau lien' }}
      </router-link>
    </nav>
  </q-form>
</template>

<style lang="scss" scoped>
@use './auth-form';

.auth-form__account {
  font-weight: 600;
  overflow-wrap: anywhere;
}
</style>
