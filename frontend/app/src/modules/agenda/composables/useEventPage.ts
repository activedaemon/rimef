// Fiche d'un événement : chargement par slug (rechargée quand il change), état « introuvable »,
// actualisation après la fenêtre « Ma participation » ; « Disponible pour se retrouver » (déjà
// inscrite) affiché tout de suite puis fiche actualisée, annulé si l'API refuse.
import { isAxiosError } from 'axios';
import { onBeforeUnmount, ref, watch, type WatchSource } from 'vue';

import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import { fetchEvent, setMeeting, type EventDetail } from '../services/events';

export function useEventPage(slug: WatchSource<string | null | undefined>) {
  const notify = useNotify();

  const event = ref<EventDetail | null>(null);
  const loading = ref(false);
  const notFound = ref(false);
  let controller: AbortController | null = null;

  async function load(value: string | null | undefined): Promise<void> {
    controller?.abort();
    event.value = null;
    notFound.value = false;
    if (!value) return;

    const current = (controller = new AbortController());
    loading.value = true;
    try {
      event.value = await fetchEvent(value, current.signal);
    } catch (error) {
      if (current.signal.aborted) return;
      if (isAxiosError(error) && error.response?.status === 404) {
        notFound.value = true;
      } else {
        notify.error(extractApiError(error, 'L’événement n’a pas pu être chargé. Réessayez.'));
      }
    } finally {
      if (controller === current) loading.value = false;
    }
  }

  /** Fiche rechargée sans l'effacer (participation, liste des médiatrices présentes). */
  async function reload(): Promise<void> {
    const current = event.value;
    if (!current) return;
    try {
      event.value = await fetchEvent(current.slug);
    } catch {
      // Fiche déjà affichée : elle reste telle quelle
    }
  }

  async function toggleMeeting(): Promise<void> {
    const current = event.value;
    if (!current) return;

    const available = !current.wants_to_meet;
    current.wants_to_meet = available;
    try {
      await setMeeting(current.id, available);
      notify.success(
        available
          ? 'Les médiatrices présentes verront que vous souhaitez les retrouver.'
          : 'Votre disponibilité n’est plus affichée.'
      );
      await reload();
    } catch (error) {
      current.wants_to_meet = !available;
      notify.error(extractApiError(error, 'Votre disponibilité n’a pas pu être enregistrée.'));
    }
  }

  watch(slug, load, { immediate: true });
  onBeforeUnmount(() => controller?.abort());

  return { event, loading, notFound, reload, toggleMeeting };
}
