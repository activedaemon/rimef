<script setup lang="ts">
// Modifier mon profil (maquette pages-construites/edit) : sections Photo, Identité, Biographie,
// Pratique, Domaines d'expertise, Zones d'intervention, Liens ; aperçu de la carte de l'annuaire.
// Pas de section Langues (abandonnée). Un compte d'administration sans fiche ne modifie que sa
// photo, son prénom et son nom. Quitter la page avec des changements non enregistrés demande confirmation.
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { onBeforeRouteLeave, useRouter } from 'vue-router';

import AppButton from '@/components/AppButton.vue';
import BackLink from '@/components/BackLink.vue';
import PageIntro from '@/components/PageIntro.vue';
import { useSession } from '@/stores/session';
import { fetchMemberProfile, type MemberProfile } from '@modules/network/services/members';
import ExpertisesField from '../components/ExpertisesField.vue';
import LinksFields from '../components/LinksFields.vue';
import ProfilePhotoField from '../components/ProfilePhotoField.vue';
import ProfilePreview from '../components/ProfilePreview.vue';
import ZonesField from '../components/ZonesField.vue';
import { useProfileForm } from '../composables/useProfileForm';

const BIO_MAX = 5000;
const TAGLINE_MAX = 200;
const CURRENT_YEAR = new Date().getFullYear();

const router = useRouter();
const session = useSession();
const {
  profile,
  options,
  form,
  loading,
  saving,
  errors,
  dirty,
  photo,
  photoRemoved,
  displayedPhoto,
  load,
  save,
  reset,
  choosePhoto,
  revertPhoto,
  removePhoto,
} = useProfileForm();

const hasSheet = computed(() => profile.value?.has_sheet ?? false);
const fullName = computed(() =>
  form.value ? `${form.value.first_name} ${form.value.last_name}`.trim() : ''
);
const updatedOn = computed(() =>
  profile.value?.updated_at
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(
        new Date(profile.value.updated_at)
      )
    : null
);

// Fiche publiée : événements et ressources de la colonne « Relié à votre profil »
const sheet = ref<MemberProfile | null>(null);
watch(hasSheet, async (value) => {
  const slug = session.user?.slug;
  if (!value || !slug) return;
  sheet.value = await fetchMemberProfile(slug).catch(() => null);
});

async function submit(): Promise<void> {
  await save();
}

function cancel(): void {
  if (dirty.value) {
    reset();
  } else {
    // Sans fiche, /profil ramène ici (router/index.ts)
    void router.push({ name: hasSheet.value ? 'profile' : 'home' });
  }
}

// Confirmation avant de quitter la page avec des changements non enregistrés
const leaveOpen = ref(false);
let leaveAnswer: ((leave: boolean) => void) | null = null;

function answerLeave(leave: boolean): void {
  leaveOpen.value = false;
  leaveAnswer?.(leave);
  leaveAnswer = null;
}

onBeforeRouteLeave(() => {
  if (!dirty.value || saving.value) return true;
  leaveOpen.value = true;
  return new Promise<boolean>((resolve) => {
    leaveAnswer = resolve;
  });
});

function warnBeforeUnload(event: BeforeUnloadEvent): void {
  if (dirty.value) event.preventDefault();
}

onMounted(() => {
  window.addEventListener('beforeunload', warnBeforeUnload);
  void load();
});
onBeforeUnmount(() => window.removeEventListener('beforeunload', warnBeforeUnload));
</script>

<template>
  <q-page class="inner-page edit-page" :class="{ 'edit-page--docked': dirty }">
    <div class="container">
      <!-- Sans fiche, ni lien de retour ni boutons dans l'en-tête : intro des pages de premier niveau -->
      <PageIntro
        v-if="profile && !hasSheet"
        title="Modifier mon profil"
        lead="Votre compte d’administration n’apparaît pas dans l’annuaire du réseau."
      />

      <!-- Attendre le profil : un compte sans fiche ne doit pas voir passer l'en-tête des membres -->
      <template v-else-if="profile">
        <BackLink :to="{ name: 'profile' }" label="Retour à mon profil" />

        <header class="edit-head">
          <div>
            <h1>Modifier mon profil</h1>
            <p class="edit-head__sub">
              <q-icon name="eye" size="17px" aria-hidden="true" />
              Les informations ci-dessous sont visibles par les autres membres du réseau.
            </p>
          </div>
          <div v-if="form" class="edit-head__actions">
            <AppButton variant="quiet" label="Annuler" :disable="saving" @click="cancel" />
            <AppButton label="Enregistrer" :loading="saving" :disable="!dirty" @click="submit" />
          </div>
        </header>
      </template>

      <div v-if="loading" class="edit-loading">
        <q-spinner size="32px" color="primary" aria-label="Chargement du profil" />
      </div>

      <form
        v-else-if="form"
        class="edit-body"
        :class="{ 'edit-body--short': !hasSheet }"
        novalidate
        @submit.prevent="submit"
      >
        <q-card flat class="rimef-card edit-main">
          <section class="edit-sec" aria-labelledby="sec-photo">
            <h2 id="sec-photo">Photo</h2>
            <p class="edit-help">
              {{
                hasSheet
                  ? 'Elle apparaît sur votre profil, dans l’annuaire du réseau et à côté de vos participations.'
                  : 'Elle apparaît à côté de votre nom dans la messagerie et dans l’administration.'
              }}
            </p>
            <ProfilePhotoField
              :name="fullName"
              :photo="displayedPhoto"
              :changed="photo !== null || photoRemoved"
              :has-saved="!!profile?.photo_url"
              :error="errors.photo"
              @choose="choosePhoto"
              @revert="revertPhoto"
              @remove="removePhoto"
            />
          </section>

          <section class="edit-sec" aria-labelledby="sec-identity">
            <h2 id="sec-identity">Identité</h2>
            <div class="grid-2">
              <div>
                <label class="field-label" for="first-name">Prénom</label>
                <q-input
                  v-model="form.first_name"
                  for="first-name"
                  outlined
                  dense
                  maxlength="100"
                  autocomplete="given-name"
                  :rules="[(value: string) => !!value.trim() || 'Indiquez votre prénom.']"
                  lazy-rules
                  :error="!!errors.first_name"
                  :error-message="errors.first_name"
                />
              </div>
              <div>
                <label class="field-label" for="last-name">Nom</label>
                <q-input
                  v-model="form.last_name"
                  for="last-name"
                  outlined
                  dense
                  maxlength="100"
                  autocomplete="family-name"
                  :rules="[(value: string) => !!value.trim() || 'Indiquez votre nom.']"
                  lazy-rules
                  :error="!!errors.last_name"
                  :error-message="errors.last_name"
                />
              </div>

              <template v-if="hasSheet">
                <div class="grid-2__wide">
                  <label class="field-label" for="job-title">Fonction</label>
                  <q-input
                    v-model="form.job_title"
                    for="job-title"
                    outlined
                    dense
                    maxlength="150"
                    hint="Par exemple « Conseillère en paix et sécurité ». Sans fonction, votre profil indique « Médiatrice »."
                    :error="!!errors.job_title"
                    :error-message="errors.job_title"
                  />
                </div>
                <div>
                  <label class="field-label" for="organization-type">Type d’organisation</label>
                  <q-select
                    v-model="form.organization_type"
                    for="organization-type"
                    outlined
                    dense
                    clearable
                    emit-value
                    map-options
                    :options="options.organization_types"
                    hint="Sert au filtre Organisation de la page Réseau."
                    :error="!!errors.organization_type"
                    :error-message="errors.organization_type"
                  />
                </div>
                <div>
                  <label class="field-label" for="organization-name">
                    Organisation <span class="field-optional">(facultatif)</span>
                  </label>
                  <q-input
                    v-model="form.organization_name"
                    for="organization-name"
                    outlined
                    dense
                    maxlength="150"
                    autocomplete="organization"
                    :error="!!errors.organization_name"
                    :error-message="errors.organization_name"
                  />
                </div>
                <div>
                  <label class="field-label" for="country">Pays</label>
                  <q-select
                    v-model="form.country_code"
                    for="country"
                    outlined
                    dense
                    clearable
                    emit-value
                    map-options
                    option-value="code"
                    option-label="name"
                    :options="options.countries"
                    :error="!!errors.country_code"
                    :error-message="errors.country_code"
                  />
                </div>
                <div>
                  <label class="field-label" for="city">Ville</label>
                  <q-input
                    v-model="form.city"
                    for="city"
                    outlined
                    dense
                    maxlength="100"
                    autocomplete="address-level2"
                    :error="!!errors.city"
                    :error-message="errors.city"
                  />
                </div>
              </template>
            </div>
          </section>

          <template v-if="hasSheet">
            <section class="edit-sec" aria-labelledby="sec-bio">
              <h2 id="sec-bio">Biographie</h2>
              <p class="edit-help">
                Présentez votre parcours, les processus que vous avez accompagnés et ce qui guide
                votre pratique.
              </p>
              <label class="field-label" for="bio">Biographie</label>
              <q-input
                v-model="form.bio"
                for="bio"
                type="textarea"
                outlined
                autogrow
                counter
                :maxlength="BIO_MAX"
                hint="Affichée dans la partie « À propos » de votre profil."
                class="edit-bio"
                :error="!!errors.bio"
                :error-message="errors.bio"
              />
              <label class="field-label edit-tagline-label" for="tagline">
                Phrase d’engagement <span class="field-optional">(sous votre nom)</span>
              </label>
              <q-input
                v-model="form.tagline"
                for="tagline"
                outlined
                dense
                counter
                :maxlength="TAGLINE_MAX"
                class="edit-tagline"
                :error="!!errors.tagline"
                :error-message="errors.tagline"
              />
            </section>

            <section class="edit-sec" aria-labelledby="sec-practice">
              <h2 id="sec-practice">Pratique</h2>
              <p class="edit-help">Repères affichés en haut de votre profil.</p>
              <div class="grid-2">
                <div>
                  <label class="field-label" for="experience-since">
                    Année de début en médiation
                  </label>
                  <q-input
                    v-model.number="form.experience_since"
                    for="experience-since"
                    type="number"
                    inputmode="numeric"
                    outlined
                    dense
                    :min="1950"
                    :max="CURRENT_YEAR"
                    :hint="`Entre 1950 et ${CURRENT_YEAR}.`"
                    :error="!!errors.experience_since"
                    :error-message="errors.experience_since"
                  />
                </div>
                <div>
                  <label class="field-label" for="audiences">Publics accompagnés</label>
                  <q-input
                    v-model="form.audiences"
                    for="audiences"
                    outlined
                    dense
                    maxlength="200"
                    hint="Par exemple « Femmes, jeunes, leaders communautaires »."
                    :error="!!errors.audiences"
                    :error-message="errors.audiences"
                  />
                </div>
              </div>
              <q-toggle
                v-model="form.is_available"
                color="primary"
                label="Disponible pour de nouvelles collaborations"
                class="edit-available"
              />
            </section>

            <section class="edit-sec" aria-labelledby="sec-expertises">
              <h2 id="sec-expertises">Domaines d’expertise</h2>
              <p class="edit-help">
                Les deux premiers apparaissent sur votre carte dans l’annuaire. Ils servent aussi
                aux filtres de recherche.
              </p>
              <ExpertisesField
                v-model="form.expertises"
                :options="options.expertises"
                :error="errors.expertises"
              />
            </section>

            <section class="edit-sec" aria-labelledby="sec-zones">
              <h2 id="sec-zones">Zones d’intervention</h2>
              <p class="edit-help">Pays où vous intervenez.</p>
              <ZonesField
                v-model="form.zones"
                :countries="options.countries"
                :error="errors.zones"
              />
            </section>

            <section class="edit-sec" aria-labelledby="sec-links">
              <h2 id="sec-links">Liens</h2>
              <p class="edit-help">
                Affichés sur votre profil. Laissez vide ce que vous ne souhaitez pas partager.
              </p>
              <LinksFields v-model="form.links" :errors="errors" />
            </section>
          </template>

          <footer class="edit-foot">
            <p v-if="updatedOn" class="edit-foot__date">Dernière mise à jour le {{ updatedOn }}</p>
            <div class="edit-foot__actions">
              <AppButton variant="quiet" label="Annuler" :disable="saving" @click="cancel" />
              <AppButton type="submit" label="Enregistrer" :loading="saving" :disable="!dirty" />
            </div>
          </footer>
        </q-card>

        <ProfilePreview
          v-if="hasSheet"
          :form="form"
          :options="options"
          :photo="displayedPhoto"
          :slug="session.user?.slug ?? 'profil'"
          :sheet="sheet"
        />
      </form>
    </div>

    <q-page-sticky v-if="dirty && $q.screen.lt.sm" position="bottom" expand class="edit-dock">
      <div class="edit-dock__bar">
        <p>Modifications non enregistrées</p>
        <AppButton variant="quiet" label="Annuler" :disable="saving" @click="cancel" />
        <AppButton label="Enregistrer" :loading="saving" @click="submit" />
      </div>
    </q-page-sticky>

    <q-dialog v-model="leaveOpen" persistent @hide="answerLeave(false)">
      <q-card class="rimef-card leave-dialog" role="alertdialog" aria-labelledby="leave-title">
        <h2 id="leave-title">Quitter sans enregistrer ?</h2>
        <p>Vos modifications ne seront pas conservées.</p>
        <div class="leave-dialog__actions">
          <AppButton variant="quiet" label="Rester sur la page" @click="answerLeave(false)" />
          <AppButton variant="accent" label="Quitter" @click="answerLeave(true)" />
        </div>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<style lang="scss" scoped>
.edit-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  padding-block: 8px 28px;
  border-bottom: 1px solid var(--border);

  h1 {
    font-size: var(--fs-h1);
    line-height: 1.02;
    letter-spacing: -0.012em;
  }

  &__sub {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    color: var(--ink-2);

    .q-icon {
      color: var(--petrol);
    }
  }

  &__actions {
    display: flex;
    flex: none;
    gap: 10px;
  }
}

.edit-loading {
  display: grid;
  place-items: center;
  padding-block: var(--s-16);
}

.edit-body {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  gap: 24px;
  align-items: start;
  padding-block: 28px 72px;

  // Sans fiche : carte seule, alignée sur le titre de l'intro, sans trait au-dessus
  &--short {
    grid-template-columns: minmax(0, 760px);
    padding-top: 0;
  }
}

.edit-main {
  padding: 8px 34px 0;
}

.edit-sec {
  padding-block: 28px;

  & + & {
    border-top: 1px solid var(--border);
  }

  > h2 {
    margin-bottom: 16px;
    font-size: 1.45rem;
  }

  > .edit-help {
    margin: -12px 0 18px;
  }
}

.edit-help {
  max-width: 62ch;
  font-size: var(--fs-sm);
  color: var(--muted);
}

.grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 6px 20px;

  &__wide {
    grid-column: 1 / -1;
  }
}

.field-optional {
  font-weight: 400;
  color: var(--muted);
}

.edit-tagline-label {
  margin-top: 18px;
}

// Phrase d'engagement en italique serif, comme sur la fiche
.edit-tagline :deep(input) {
  // noinspection CssNoGenericFontName
  font-family: var(--font-serif);
  font-size: 1.15rem;
  font-style: italic;
}

.edit-available {
  margin: 10px 0 0 -10px;
  min-height: 44px;
}

.edit-foot {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-inline: -34px;
  padding: 20px 34px;
  border-top: 1px solid var(--border);

  &__date {
    font-size: var(--fs-sm);
    color: var(--muted);
  }

  &__actions {
    display: flex;
    gap: 10px;
    margin-left: auto;
  }
}

// Barre du bas sur téléphone, au-dessus de la barre d'onglets
.edit-dock {
  z-index: 45;

  :deep(> div) {
    width: 100%;
  }

  &__bar {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px var(--gutter);
    border-top: 1px solid var(--border);
    background: rgba(252, 250, 246, 0.97);

    p {
      flex: 1;
      font-size: var(--fs-sm);
      font-weight: 500;
      color: var(--ink);
    }

    .app-btn {
      padding-inline: 14px;
    }
  }
}

.leave-dialog {
  width: min(420px, calc(100vw - 32px));
  padding: 24px;

  h2 {
    font-size: 1.45rem;
  }

  p {
    margin-top: 8px;
    color: var(--ink-2);
  }

  &__actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 24px;
  }
}

// Tablette : colonne de droite sous le formulaire
@media (max-width: 1079px) {
  .edit-body {
    grid-template-columns: minmax(0, 1fr);
  }

  .edit-main {
    padding-inline: 28px;
  }

  .edit-foot {
    margin-inline: -28px;
    padding-inline: 28px;
  }
}

// Téléphone : actions dans la barre du bas, champs sur une colonne
@media (max-width: 719px) {
  .edit-head {
    padding-bottom: 20px;

    &__actions {
      display: none;
    }
  }

  .edit-body {
    padding-top: 20px;
  }

  .edit-main {
    padding-inline: 18px;
  }

  .edit-foot {
    margin-inline: -18px;
    padding-inline: 18px;
  }

  .grid-2 {
    grid-template-columns: 1fr;
  }

  .edit-page--docked .edit-body {
    padding-bottom: 96px;
  }
}
</style>
