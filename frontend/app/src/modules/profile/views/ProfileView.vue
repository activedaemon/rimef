<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageIntro from '@/components/PageIntro.vue';
import { useSession } from '@/stores/session';

const session = useSession();
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro :title="session.user?.name ?? 'Mon profil'" :eyebrow="session.roleLabel">
        <AppButton
          variant="outline"
          icon="pencil"
          label="Modifier mon profil"
          :to="{ name: 'profile-edit' }"
        />
      </PageIntro>

      <q-card flat bordered class="profile-card">
        <q-list>
          <q-item>
            <q-item-section avatar><q-icon name="mail" /></q-item-section>
            <q-item-section>
              <q-item-label caption>Adresse email</q-item-label>
              <q-item-label>{{ session.user?.email }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <EmptyState
        icon="id-badge-2"
        title="Votre parcours arrive bientôt"
        text="Expertises et événements auxquels vous participez seront affichés ici."
      />
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.profile-card {
  max-width: 560px;
  margin-bottom: var(--s-8);
  background: var(--card);
  border-color: var(--border);
}
</style>
