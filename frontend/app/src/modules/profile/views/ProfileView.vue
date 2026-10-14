<script setup lang="ts">
// Mon profil : une médiatrice (rôle member) voit sa propre fiche, comme dans l'annuaire ;
// un compte d'administration sans ce rôle n'a pas de fiche : même en-tête, puis une carte « Compte ».
import { computed } from 'vue';

import AppButton from '@/components/AppButton.vue';
import { useSession } from '@/stores/session';
import MemberProfileSheet from '@modules/network/components/MemberProfileSheet.vue';
import ProfileHeader from '@modules/network/components/ProfileHeader.vue';
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

    <div v-else-if="session.user" class="container account-profile">
      <ProfileHeader
        :profile="session.user"
        :badges="session.roleLabels"
        badge-tone="petrol"
        role="Équipe RIMeF"
      >
        <template #actions>
          <AppButton
            variant="outline"
            icon="pencil"
            label="Modifier mon profil"
            :to="{ name: 'profile-edit' }"
          />
        </template>
      </ProfileHeader>

      <q-card flat tag="section" class="rimef-card account-card" aria-labelledby="account-title">
        <h2 id="account-title">Compte</h2>
        <dl class="account-card__facts">
          <div>
            <dt>Adresse email</dt>
            <dd>{{ session.user.email }}</dd>
          </div>
        </dl>
        <p class="account-card__note">
          <q-icon name="eye-off" size="16px" aria-hidden="true" />
          Ce compte d’administration n’apparaît pas dans l’annuaire du réseau.
        </p>
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

.account-profile {
  padding-bottom: 48px;
}

// Même carte que le corps de la fiche d'une médiatrice
.account-card {
  display: grid;
  gap: 22px;
  max-width: 760px;
  padding: 34px 38px 38px;

  h2 {
    font-size: 1.45rem;
  }

  &__facts {
    display: grid;
    gap: 16px;
    margin: 0;

    dt {
      font-size: var(--fs-xs);
      color: var(--muted);
    }

    dd {
      margin: 2px 0 0;
      overflow-wrap: anywhere;
    }
  }

  &__note {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
    font-size: var(--fs-sm);
    color: var(--ink-2);
  }
}

@media (max-width: 1079px) {
  .account-card {
    padding: 26px 28px 30px;
  }
}

@media (max-width: 719px) {
  .account-card {
    padding: 22px 20px 24px;
  }
}
</style>
