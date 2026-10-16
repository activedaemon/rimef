<script setup lang="ts">
// Activation d'un compte invité (lien de l'email d'invitation : ?token=…&email=…) : l'invitée
// choisit son mot de passe, puis se connecte (email prérempli). Même présentation que
// « Nouveau mot de passe ».
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import AppButton from '@/components/AppButton.vue';
import { extractApiError } from '@/lib/http';
import { acceptInvitation } from '../services/auth';

/** Longueur minimale, alignée sur Password::defaults() du backend. */
const PASSWORD_MIN_LENGTH = 12;

const route = useRoute();
const router = useRouter();

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
    await acceptInvitation({
      token,
      email,
      password: form.password,
      password_confirmation: form.confirmation,
    });
    await router.replace({ name: 'login', query: { active: '1', email } });
  } catch (exception) {
    error.value = extractApiError(exception, 'L’activation a échoué. Réessayez.');
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <q-form class="auth-form" novalidate @submit="submit">
    <header class="auth-form__header">
      <h1 class="auth-form__title">Activer mon compte</h1>
      <template v-if="linkIsComplete">
        <p class="auth-form__lead">
          Bienvenue dans le réseau. Choisissez le mot de passe de votre compte.
        </p>
        <p class="auth-form__account">{{ email }}</p>
      </template>
    </header>

    <template v-if="linkIsComplete">
      <p v-if="error" class="auth-form__alert" role="alert">{{ error }}</p>

      <div>
        <label for="activate-password" class="field-label">Mot de passe</label>
        <q-input
          v-model="form.password"
          for="activate-password"
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
        <label for="activate-confirmation" class="field-label">Confirmez le mot de passe</label>
        <q-input
          v-model="form.confirmation"
          for="activate-confirmation"
          :type="showPassword ? 'text' : 'password'"
          autocomplete="new-password"
          outlined
          dense
          hide-bottom-space
          lazy-rules
          :rules="confirmationRules"
        />
      </div>

      <AppButton
        type="submit"
        label="Activer mon compte"
        class="auth-form__submit"
        :loading="submitting"
      />
    </template>

    <p v-else class="auth-form__alert" role="alert">
      Ce lien est incomplet ou a été mal copié. Ouvrez de nouveau le lien reçu par email, ou
      demandez une nouvelle invitation à l’équipe du réseau.
    </p>

    <nav class="auth-form__links" aria-label="Retour">
      <router-link :to="{ name: 'login' }" class="auth-form__link"
        >Aller à la connexion</router-link
      >
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
