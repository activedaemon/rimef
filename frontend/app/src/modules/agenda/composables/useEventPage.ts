// Fiche d'un événement : chargement par slug (rechargée quand il change), état « introuvable »,
// « J'y participe » affiché tout de suite puis fiche actualisée (liste des médiatrices présentes),
// annulé si l'API refuse ; de même pour « Proposer de se retrouver sur place ».
import { isAxiosError } from 'axios';
import { onBeforeUnmount, ref, watch, type WatchSource } from 'vue';

import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import { fetchEvent, setMeeting, setParticipation, type EventDetail } from '../services/events';

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

  async function toggleParticipation(): Promise<void> {
    const current = event.value;
    if (!current) return;

    const participating = !current.is_participating;
    current.is_participating = participating;
    current.attendee_count += participating ? 1 : -1;
    try {
      await setParticipation(current.id, participating);
      notify.success(
        participating
          ? `C’est noté : vous participez à « ${current.title} ».`
          : `Votre participation à « ${current.title} » est retirée.`
      );
      // Liste des médiatrices présentes à jour (la personne connectée y entre ou en sort)
      event.value = await fetchEvent(current.slug).catch(() => current);
    } catch (error) {
      current.is_participating = !participating;
      current.attendee_count += participating ? -1 : 1;
      notify.error(extractApiError(error, 'Votre participation n’a pas pu être enregistrée.'));
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
      // Se proposer inscrit aussi à l'événement : participation et liste à jour
      event.value = await fetchEvent(current.slug).catch(() => current);
    } catch (error) {
      current.wants_to_meet = !available;
      notify.error(extractApiError(error, 'Votre disponibilité n’a pas pu être enregistrée.'));
    }
  }

  watch(slug, load, { immediate: true });
  onBeforeUnmount(() => controller?.abort());

  return { event, loading, notFound, toggleParticipation, toggleMeeting };
}
