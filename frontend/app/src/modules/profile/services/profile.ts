// Page « Modifier mon profil » : lecture et enregistrement du profil du compte connecté.
import { ensureCsrf, http } from '@/lib/http';

export interface ProfileLinks {
  linkedin: string | null;
  website: string | null;
  organization: string | null;
  other: { label: string | null; url: string | null };
}

/** Valeurs modifiables ; `has_sheet` faux pour un compte d'administration hors annuaire. */
export interface EditableProfile {
  has_sheet: boolean;
  first_name: string;
  last_name: string;
  photo_url: string | null;
  job_title: string | null;
  organization_type: string | null;
  organization_name: string | null;
  country_code: string | null;
  city: string | null;
  tagline: string | null;
  bio: string | null;
  experience_since: number | null;
  audiences: string | null;
  is_available: boolean;
  /** Noms des expertises, dans l'ordre choisi (les deux premières sur la carte de l'annuaire). */
  expertises: string[];
  /** Codes pays des zones d'intervention, dans l'ordre de saisie. */
  zones: string[];
  links: ProfileLinks;
  /** ISO 8601. */
  updated_at: string | null;
}

export type ProfileForm = Omit<EditableProfile, 'has_sheet' | 'photo_url' | 'updated_at'>;

export interface ProfileOptions {
  countries: { code: string; name: string }[];
  organization_types: { value: string; label: string }[];
  /** Expertises du réseau, les plus portées d'abord. */
  expertises: { name: string; count: number }[];
}

export async function fetchEditableProfile(): Promise<{
  profile: EditableProfile;
  options: ProfileOptions;
}> {
  const { data } = await http.get<{ data: EditableProfile; meta: { options: ProfileOptions } }>(
    '/profile'
  );
  return { profile: data.data, options: data.meta.options };
}

export async function updateProfile(form: ProfileForm): Promise<EditableProfile> {
  await ensureCsrf();
  const { data } = await http.put<{ data: EditableProfile }>('/profile', form);
  return data.data;
}

export async function uploadProfilePhoto(photo: File): Promise<EditableProfile> {
  await ensureCsrf();
  const body = new FormData();
  body.append('photo', photo);
  const { data } = await http.post<{ data: EditableProfile }>('/profile/photo', body);
  return data.data;
}

export async function deleteProfilePhoto(): Promise<EditableProfile> {
  await ensureCsrf();
  const { data } = await http.delete<{ data: EditableProfile }>('/profile/photo');
  return data.data;
}

/** Champs du formulaire tirés des valeurs enregistrées. */
export function toForm(profile: EditableProfile): ProfileForm {
  return {
    first_name: profile.first_name,
    last_name: profile.last_name,
    job_title: profile.job_title,
    organization_type: profile.organization_type,
    organization_name: profile.organization_name,
    country_code: profile.country_code,
    city: profile.city,
    tagline: profile.tagline,
    bio: profile.bio,
    experience_since: profile.experience_since,
    audiences: profile.audiences,
    is_available: profile.is_available,
    expertises: [...profile.expertises],
    zones: [...profile.zones],
    links: { ...profile.links, other: { ...profile.links.other } },
  };
}
