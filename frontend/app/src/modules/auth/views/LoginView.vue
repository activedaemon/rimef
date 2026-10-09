<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import AppButton from '@/components/AppButton.vue';
import { extractApiError } from '@/lib/http';
import { safeRedirect } from '@/lib/redirect';
import { useSession } from '@/stores/session';

const session = useSession();
const route = useRoute();
const router = useRouter();

const form = reactive({ email: '', password: '', remember: false });
const showPassword = ref(false);
const submitting = ref(false);
const error = ref('');

const passwordWasReset = route.query.reinitialise === '1';

const emailRules = [
  (value: string) => !!value || 'Indiquez votre adresse email.',
  (value: string) =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Cette adresse email n’est pas valide.',
];
const passwordRules = [(value: string) => !!value || 'Indiquez votre mot de passe.'];

async function submit(): Promise<void> {
  submitting.value = true;
  error.value = '';
  try {
    await session.login({ ...form });
    await router.replace(safeRedirect(route.query.redirect));
  } catch (exception) {
    error.value = extractApiError(exception, 'La connexion a échoué. Réessayez dans un instant.');
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <q-form class="auth-form" novalidate @submit="submit">
    <header class="auth-form__header">
      <h1 class="auth-form__title">Connexion</h1>
      <p class="auth-form__lead">Retrouvez le réseau, l’agenda et les ressources.</p>
    </header>

    <p v-if="passwordWasReset" class="auth-form__notice" role="status">
      Votre mot de passe a été réinitialisé. Connectez-vous avec le nouveau.
    </p>
    <p v-if="error" class="auth-form__alert" role="alert">{{ error }}</p>

    <div>
      <label for="login-email" class="field-label">Adresse email</label>
      <q-input
        v-model.trim="form.email"
        for="login-email"
        type="email"
        autocomplete="username"
        outlined
        dense
        hide-bottom-space
        lazy-rules
        :rules="emailRules"
      />
    </div>

    <div>
      <label for="login-password" class="field-label">Mot de passe</label>
      <q-input
        v-model="form.password"
        for="login-password"
        :type="showPassword ? 'text' : 'password'"
        autocomplete="current-password"
        outlined
        dense
        hide-bottom-space
        lazy-rules
        :rules="passwordRules"
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

    <q-checkbox
      v-model="form.remember"
      label="Rester connectée"
      color="primary"
      class="auth-form__remember"
    />

    <AppButton type="submit" label="Se connecter" class="auth-form__submit" :loading="submitting" />

    <nav class="auth-form__links" aria-label="Aide à la connexion">
      <router-link :to="{ name: 'forgot-password' }" class="auth-form__link">
        Mot de passe oublié ?
      </router-link>
    </nav>
  </q-form>
</template>

<style lang="scss" scoped>
@use './auth-form';
</style>
