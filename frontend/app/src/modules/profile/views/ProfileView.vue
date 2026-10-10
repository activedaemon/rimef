<script setup lang="ts">
// Mon profil : une médiatrice (rôle member) voit sa propre fiche, comme dans l'annuaire ;
// un compte d'administration sans ce rôle n'a pas de fiche, seulement son identité.
import { computed } from 'vue';

import AppButton from '@/components/AppButton.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import PageIntro from '@/components/PageIntro.vue';
import { useSession } from '@/stores/session';
import MemberProfileSheet from '@modules/network/components/MemberProfileSheet.vue';
import { useMemberProfile } from '@modules/network/composables/useMemberProfile';

const session = useSession();
const isMember = computed(() => session.user?.roles.includes('member') ?? false);

const { profile, loading } = useMemberProfile(() =>
  isMember.value ? (session.user?.slug ?? null) : null
);
</script>

<template>
  <q-page class="inner-page">
    <template v-if="isMember">
      <MemberProfileSheet v-if="profile" :profile="profile">
        <template #actions>
          <AppButton
            variant="outline"
            icon="pencil"
            label="Modifier mon profil"
            :to="{ name: 'profile-edit' }"
          />
        </template>
      </MemberProfileSheet>

      <div v-else-if="loading" class="container profile-loading">
        <q-spinner size="32px" color="primary" aria-label="Chargement du profil" />
      </div>
    </template>

    <div v-else-if="session.user" class="container">
      <PageIntro :title="session.user.name" :eyebrow="session.roleLabel">
        <AppButton
          variant="outline"
          icon="pencil"
          label="Modifier mon profil"
          :to="{ name: 'profile-edit' }"
        />
      </PageIntro>

      <q-card flat class="rimef-card account-card">
        <q-list>
          <q-item>
            <q-item-section avatar>
              <MemberAvatar :name="session.user.name" :size="48" />
            </q-item-section>
            <q-item-section>
              <q-item-label caption>Adresse email</q-item-label>
              <q-item-label>{{ session.user.email }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.profile-loading {
  display: grid;
  place-items: center;
  padding-block: var(--s-16);
}

.account-card {
  max-width: 560px;
}
</style>
