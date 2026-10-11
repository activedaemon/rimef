// Fiche d'une médiatrice : chargement par slug (rechargée quand il change), état « introuvable »,
// favori enregistré sans attendre la réponse (rétabli en cas d'échec), actualisation sans
// effacer la fiche affichée (après un premier message : onglet Messages).
import { isAxiosError } from 'axios';
import { onBeforeUnmount, ref, watch, type WatchSource } from 'vue';

import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import { fetchMemberProfile, setFavorite, type MemberProfile } from '../services/members';

export function useMemberProfile(slug: WatchSource<string | null | undefined>) {
  const notify = useNotify();

  const profile = ref<MemberProfile | null>(null);
  const loading = ref(false);
  const notFound = ref(false);
  let controller: AbortController | null = null;

  async function load(value: string | null | undefined): Promise<void> {
    controller?.abort();
    profile.value = null;
    notFound.value = false;
    if (!value) return;

    const current = (controller = new AbortController());
    loading.value = true;
    try {
      profile.value = await fetchMemberProfile(value, current.signal);
    } catch (error) {
      if (current.signal.aborted) return;
      if (isAxiosError(error) && error.response?.status === 404) {
        notFound.value = true;
      } else {
        notify.error(extractApiError(error, 'Le profil n’a pas pu être chargé. Réessayez.'));
      }
    } finally {
      if (controller === current) loading.value = false;
    }
  }

  async function refresh(): Promise<void> {
    const member = profile.value;
    if (!member) return;
    try {
      profile.value = await fetchMemberProfile(member.slug);
    } catch {
      // Fiche déjà affichée : elle reste telle quelle
    }
  }

  async function toggleFavorite(): Promise<void> {
    const member = profile.value;
    if (!member) return;

    const favorite = !member.is_favorite;
    member.is_favorite = favorite;
    try {
      await setFavorite(member.id, favorite);
    } catch (error) {
      member.is_favorite = !favorite;
      notify.error(extractApiError(error, 'Le favori n’a pas pu être enregistré. Réessayez.'));
    }
  }

  watch(slug, load, { immediate: true });
  onBeforeUnmount(() => controller?.abort());

  return { profile, loading, notFound, refresh, toggleFavorite };
}
