// Fiche d'une ressource : chargement par slug (rechargée quand il change), état « introuvable »,
// favori affiché tout de suite, annulé si l'API refuse.
import { isAxiosError } from 'axios';
import { onBeforeUnmount, ref, watch, type WatchSource } from 'vue';

import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import { fetchResource, setFavorite, type ResourceDetail } from '../services/resources';

export function useResourcePage(slug: WatchSource<string | null | undefined>) {
  const notify = useNotify();

  const resource = ref<ResourceDetail | null>(null);
  const loading = ref(false);
  const notFound = ref(false);
  let controller: AbortController | null = null;

  async function load(value: string | null | undefined): Promise<void> {
    controller?.abort();
    resource.value = null;
    notFound.value = false;
    if (!value) return;

    const current = (controller = new AbortController());
    loading.value = true;
    try {
      resource.value = await fetchResource(value, current.signal);
    } catch (error) {
      if (current.signal.aborted) return;
      if (isAxiosError(error) && error.response?.status === 404) {
        notFound.value = true;
      } else {
        notify.error(extractApiError(error, 'La ressource n’a pas pu être chargée. Réessayez.'));
      }
    } finally {
      if (controller === current) loading.value = false;
    }
  }

  async function toggleFavorite(): Promise<void> {
    const current = resource.value;
    if (!current) return;

    const favorite = !current.is_favorite;
    current.is_favorite = favorite;
    try {
      await setFavorite(current.id, favorite);
      notify.success(
        favorite ? 'Ressource ajoutée à vos favoris.' : 'Ressource retirée de vos favoris.'
      );
    } catch (error) {
      current.is_favorite = !favorite;
      notify.error(extractApiError(error, 'Le favori n’a pas pu être enregistré. Réessayez.'));
    }
  }

  watch(slug, load, { immediate: true });
  onBeforeUnmount(() => controller?.abort());

  return { resource, loading, notFound, toggleFavorite };
}
