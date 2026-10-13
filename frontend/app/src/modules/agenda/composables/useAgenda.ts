// État de l'Agenda : onglet, recherche et page vivent dans l'URL (lien partageable, bouton
// retour d'une page à l'autre) ; les valeurs des filtres dépendent de l'onglet.
import { isAxiosError } from 'axios';
import { computed, onBeforeUnmount, ref, shallowRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { useNotify } from '@/composables/useNotify';
import { optionLabel } from '@/lib/filters';
import { extractApiError } from '@/lib/http';
import {
  FILTER_KEYS,
  fetchEventFilters,
  fetchEvents,
  dayLabel,
  isFiltered,
  monthLabel,
  queryFromRoute,
  routeFromQuery,
  todayKey,
  type AgendaEvent,
  type AgendaQuery,
  type EventFilters,
  type PerPage,
  type Period,
} from '../services/events';

export function useAgenda() {
  const route = useRoute();
  const router = useRouter();
  const notify = useNotify();

  const query = computed<AgendaQuery>(() => queryFromRoute(route.query));
  const filtered = computed(() => isFiltered(query.value));

  const events = shallowRef<AgendaEvent[]>([]);
  const lastPage = ref(1);
  const total = ref(0);
  const from = ref(0);
  const to = ref(0);
  const loading = ref(false);
  const loaded = ref(false);
  const filters = shallowRef<EventFilters | null>(null);

  let controller: AbortController | null = null;

  async function load(): Promise<void> {
    controller?.abort();
    const current = new AbortController();
    controller = current;
    loading.value = true;
    try {
      const result = await fetchEvents(query.value, current.signal);
      // Page au-delà de la dernière (vieux lien, liste réduite entre-temps) : dernière page
      if (result.meta.total > 0 && result.meta.current_page > result.meta.last_page) {
        goToPage(result.meta.last_page, true);
        return;
      }
      events.value = result.data;
      lastPage.value = result.meta.last_page;
      total.value = result.meta.total;
      from.value = result.meta.from ?? 0;
      to.value = result.meta.to ?? 0;
      loaded.value = true;
    } catch (error) {
      if (isAxiosError(error) && error.code === 'ERR_CANCELED') return;
      notify.error(extractApiError(error, 'L’agenda n’a pas pu être chargé. Réessayez.'));
    } finally {
      // Une requête annulée ne termine pas le chargement de celle qui l'a remplacée
      if (controller === current) loading.value = false;
    }
  }

  async function loadFilters(period: Period): Promise<void> {
    try {
      filters.value = await fetchEventFilters(period);
    } catch {
      // Sans les valeurs des filtres, la liste et la recherche restent disponibles
      filters.value = null;
    }
  }

  /** Remplace la recherche dans l'URL (sans nouvelle entrée d'historique) et revient à la page 1. */
  function update(changes: Partial<AgendaQuery>): void {
    void router.replace({ query: routeFromQuery({ ...query.value, page: 1, ...changes }) });
  }

  /** Change de page : nouvelle entrée d'historique, le bouton retour revient à la précédente. */
  function goToPage(page: number, replace = false): void {
    const location = { query: routeFromQuery({ ...query.value, page }) };
    void (replace ? router.replace(location) : router.push(location));
  }

  /** Nouvelle taille de page : on reste sur la page qui contient le premier événement affiché. */
  function setPerPage(perPage: PerPage): void {
    const page = Math.floor((Math.max(from.value, 1) - 1) / perPage) + 1;
    void router.replace({ query: routeFromQuery({ ...query.value, page, perPage }) });
  }

  /** Autre onglet : le mois ou le jour choisi ne vaut que pour l'onglet qu'on quitte. */
  function setPeriod(period: Period): void {
    if (period !== query.value.period) update({ period, month: '', day: '' });
  }

  /** Mois du filtre Date : remplace le jour choisi dans le calendrier. */
  function setMonth(month: string): void {
    update({ month, day: '' });
  }

  /**
   * Jour du mini-calendrier (vide : aucun) : remplace le mois choisi. Un jour passé ouvre
   * l'onglet « Passés », un jour à venir « À venir » ; « Mes événements » reste ouvert.
   */
  function setDay(day: string): void {
    const current = query.value.period;
    const period = !day || current === 'mine' ? current : day < todayKey() ? 'past' : 'upcoming';
    update({ day, month: '', period });
  }

  function reset(): void {
    void router.replace({
      query: routeFromQuery({ ...queryFromRoute({}), period: query.value.period }),
    });
  }

  /** Fenêtre « Ma participation » enregistrée : la ligne et le compteur « Mes événements » suivent. */
  function onParticipationSaved(event: AgendaEvent, participating: boolean): void {
    if (event.is_participating !== participating) applyParticipation(event.id, participating);
  }

  function applyParticipation(id: number, participating: boolean): void {
    const step = participating ? 1 : -1;
    events.value = events.value.map((item) =>
      item.id === id
        ? {
            ...item,
            is_participating: participating,
            attendee_count: item.attendee_count + step,
          }
        : item
    );
    if (filters.value) {
      const periods = filters.value.periods;
      filters.value = { ...filters.value, periods: { ...periods, mine: periods.mine + step } };
    }
  }

  /** Valeurs cochées et leur libellé, pour les filtres actifs retirables. */
  const activeFilters = computed(() =>
    FILTER_KEYS.flatMap((key) =>
      query.value.selected[key].map((value) => ({
        key,
        value,
        label: optionLabel(filters.value?.[key] ?? [], value),
      }))
    )
  );

  const activeMonth = computed(() => (query.value.month ? monthLabel(query.value.month) : ''));
  const activeDay = computed(() => (query.value.day ? dayLabel(query.value.day) : ''));

  // Nouvelle recherche ou nouvelle page dans l'URL : la liste est rechargée ; nouvel onglet :
  // les valeurs des filtres aussi
  const routeName = route.name;
  watch(
    () => route.query,
    () => {
      if (route.name === routeName) void load();
    },
    { deep: true }
  );
  watch(
    () => query.value.period,
    (period) => {
      if (route.name === routeName) void loadFilters(period);
    }
  );

  onBeforeUnmount(() => controller?.abort());

  return {
    query,
    filtered,
    events,
    page: computed(() => query.value.page),
    lastPage,
    total,
    from,
    to,
    loading,
    loaded,
    filters,
    activeFilters,
    activeMonth,
    activeDay,
    update,
    goToPage,
    setPerPage,
    setPeriod,
    setMonth,
    setDay,
    reset,
    onParticipationSaved,
    start: () => Promise.all([load(), loadFilters(query.value.period)]),
  };
}
