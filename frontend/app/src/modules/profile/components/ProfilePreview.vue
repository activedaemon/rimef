<script setup lang="ts">
// Colonne de droite : la carte de l'annuaire telle que les membres la verront (mise à jour pendant
// la saisie, inerte), puis ce qui est relié au profil (événements, ressources, annuaire).
import { computed } from 'vue';

import type { Member, MemberProfile } from '@modules/network/services/members';
import MemberCard from '@modules/network/components/MemberCard.vue';
import type { ProfileForm, ProfileOptions } from '../services/profile';

const props = defineProps<{
  form: ProfileForm;
  options: ProfileOptions;
  photo: string | null;
  /** Slug de la fiche (lien de la carte, inerte dans l'aperçu). */
  slug: string;
  /** Fiche publiée (événements, ressources), absente tant qu'elle n'est pas chargée. */
  sheet: MemberProfile | null;
}>();

const member = computed<Member>(() => {
  const country = props.options.countries.find((item) => item.code === props.form.country_code);
  const type = props.options.organization_types.find(
    (item) => item.value === props.form.organization_type
  );
  const next = props.sheet?.upcoming_events[0];
  return {
    id: props.sheet?.id ?? 0,
    slug: props.slug,
    name: `${props.form.first_name} ${props.form.last_name}`.trim(),
    country: country ?? null,
    organization: props.form.organization_name?.trim() || type?.label || null,
    is_available: props.form.is_available,
    is_favorite: false,
    conversation_id: null,
    next_event: next
      ? { id: next.id, slug: next.slug, title: next.title, starts_at: next.starts_at }
      : null,
    photo_url: props.photo,
    expertises: props.form.expertises,
  };
});

const events = computed(() => props.sheet?.upcoming_events.length ?? 0);
const resources = computed(() => props.sheet?.resources.length ?? 0);
</script>

<template>
  <aside class="profile-preview" aria-label="Aperçu de votre profil">
    <q-card flat tag="section" class="rimef-card side-card" aria-labelledby="preview-title">
      <h2 id="preview-title" class="card-title">Aperçu dans l’annuaire</h2>
      <p class="side-card__help">Votre carte telle que les membres la voient sur la page Réseau.</p>
      <div class="profile-preview__card" inert>
        <MemberCard :key="photo ?? 'initiales'" :member="member" />
      </div>
    </q-card>

    <q-card
      v-if="sheet"
      flat
      tag="section"
      class="rimef-card side-card"
      aria-labelledby="linked-title"
    >
      <h2 id="linked-title" class="card-title">Relié à votre profil</h2>
      <q-list class="profile-preview__links">
        <q-item :to="{ name: 'profile', query: events ? { onglet: 'evenements' } : {} }">
          <q-item-section avatar><q-icon name="calendar-event" size="20px" /></q-item-section>
          <q-item-section>
            <q-item-label>{{
              events ? `Prochains événements : ${events}` : 'Aucun événement à venir'
            }}</q-item-label>
            <q-item-label caption>Les événements auxquels vous participez.</q-item-label>
          </q-item-section>
        </q-item>
        <q-item :to="{ name: 'profile', query: resources ? { onglet: 'ressources' } : {} }">
          <q-item-section avatar><q-icon name="file-text" size="20px" /></q-item-section>
          <q-item-section>
            <q-item-label>
              {{
                resources ? `${resources} ressource${resources > 1 ? 's' : ''}` : 'Aucune ressource'
              }}
            </q-item-label>
            <q-item-label caption
              >Celles que vous avez écrites ou auxquelles vous avez contribué.</q-item-label
            >
          </q-item-section>
        </q-item>
        <q-item :to="{ name: 'network' }">
          <q-item-section avatar><q-icon name="users" size="20px" /></q-item-section>
          <q-item-section>
            <q-item-label>Annuaire du réseau</q-item-label>
            <q-item-label caption>Votre carte figure sur la page Réseau.</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </q-card>
  </aside>
</template>

<style lang="scss" scoped>
.profile-preview {
  display: grid;
  gap: 24px;

  &__card {
    margin-top: 16px;
  }

  &__links {
    margin: 10px -16px -8px;

    .q-item {
      min-height: 56px;
    }

    :deep(.q-item__label--caption) {
      font-size: var(--fs-xs);
      color: var(--muted);
    }
  }
}

.side-card {
  padding: 24px;

  &__help {
    margin-top: 4px;
    font-size: var(--fs-sm);
    color: var(--muted);
  }
}
</style>
