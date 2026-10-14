// Page « Modifier mon profil » : copie de travail du formulaire, changements non enregistrés,
// photo choisie (envoyée seulement à l'enregistrement) et erreurs de l'API rattachées aux champs.
import { isAxiosError } from 'axios';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import { useSession } from '@/stores/session';
import {
  deleteProfilePhoto,
  fetchEditableProfile,
  toForm,
  updateProfile,
  uploadProfilePhoto,
  type EditableProfile,
  type ProfileForm,
  type ProfileOptions,
} from '../services/profile';

/** Texte vide = champ non renseigné (null côté API). */
function normalize(form: ProfileForm): ProfileForm {
  const text = (value: string | null): string | null => value?.trim() || null;
  return {
    ...form,
    first_name: form.first_name.trim(),
    last_name: form.last_name.trim(),
    job_title: text(form.job_title),
    organization_name: text(form.organization_name),
    city: text(form.city),
    tagline: text(form.tagline),
    bio: text(form.bio),
    audiences: text(form.audiences),
    experience_since: form.experience_since ? Number(form.experience_since) : null,
    links: {
      linkedin: text(form.links.linkedin),
      website: text(form.links.website),
      organization: text(form.links.organization),
      other: { label: text(form.links.other.label), url: text(form.links.other.url) },
    },
  };
}

/** Clé d'erreur de l'API → champ du formulaire (« expertises.0 » → « expertises »). */
function fieldOf(key: string): string {
  return key.replace(/\.\d+$/, '');
}

export function useProfileForm() {
  const notify = useNotify();
  const session = useSession();

  const profile = ref<EditableProfile | null>(null);
  const options = ref<ProfileOptions>({ countries: [], organization_types: [], expertises: [] });
  const form = ref<ProfileForm | null>(null);
  const loading = ref(false);
  const saving = ref(false);
  const errors = ref<Record<string, string>>({});

  const photo = ref<File | null>(null);
  const photoRemoved = ref(false);
  const photoPreview = ref<string | null>(null);

  let saved = '';

  function reset(): void {
    if (!profile.value) return;
    form.value = toForm(profile.value);
    saved = JSON.stringify(normalize(form.value));
    photo.value = null;
    photoRemoved.value = false;
    errors.value = {};
  }

  const dirty = computed(
    () =>
      form.value !== null &&
      (JSON.stringify(normalize(form.value)) !== saved ||
        photo.value !== null ||
        photoRemoved.value)
  );

  /** Photo affichée : celle choisie, sinon l'actuelle (sauf si elle vient d'être retirée). */
  const displayedPhoto = computed(() =>
    photoRemoved.value ? null : (photoPreview.value ?? profile.value?.photo_url ?? null)
  );

  watch(photo, (file) => {
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
    photoPreview.value = file ? URL.createObjectURL(file) : null;
  });
  onBeforeUnmount(() => {
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
  });

  function choosePhoto(file: File): void {
    photo.value = file;
    photoRemoved.value = false;
    delete errors.value.photo;
  }

  function revertPhoto(): void {
    photo.value = null;
    photoRemoved.value = false;
    delete errors.value.photo;
  }

  function removePhoto(): void {
    photo.value = null;
    photoRemoved.value = true;
  }

  async function load(): Promise<void> {
    loading.value = true;
    try {
      const result = await fetchEditableProfile();
      profile.value = result.profile;
      options.value = result.options;
      reset();
    } catch (error) {
      notify.error(extractApiError(error, 'Votre profil n’a pas pu être chargé. Réessayez.'));
    } finally {
      loading.value = false;
    }
  }

  /** Enregistre le formulaire puis la photo ; renvoie vrai quand tout est enregistré. */
  async function save(): Promise<boolean> {
    if (!form.value || saving.value) return false;
    saving.value = true;
    errors.value = {};
    try {
      let result = await updateProfile(normalize(form.value));
      if (photo.value) {
        result = await uploadProfilePhoto(photo.value);
      } else if (photoRemoved.value) {
        result = await deleteProfilePhoto();
      }
      profile.value = result;
      reset();
      notify.success('Votre profil est enregistré.');
      void session.refresh().catch(() => undefined);
      return true;
    } catch (error) {
      if (isAxiosError(error) && error.response?.status === 422) {
        const apiErrors = (error.response.data as { errors?: Record<string, string[]> }).errors;
        for (const [key, messages] of Object.entries(apiErrors ?? {})) {
          errors.value[fieldOf(key)] ??= messages[0] ?? '';
        }
        notify.error('Certains champs sont à corriger.');
      } else {
        notify.error(extractApiError(error, 'Votre profil n’a pas pu être enregistré. Réessayez.'));
      }
      return false;
    } finally {
      saving.value = false;
    }
  }

  return {
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
  };
}
