// État de la bibliothèque : recherche, filtres, tri et page vivent dans l'URL (lien partageable,
// bouton retour d'une page à l'autre).
import { isAxiosError } from 'axios';
import { computed, onBeforeUnmount, ref, shallowRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { useNotify } from '@/composables/useNotify';
import { optionLabel } from '@/lib/filters';
import { extractApiError } from '@/lib/http';
import {
  FILTER_KEYS,
  fetchResourceFilters,
  fetchResources,
  isFiltered,
  queryFromRoute,
  routeFromQuery,
  setSaved,
  type LibraryResource,
  type PerPage,
  type ResourceFilters,
  type ResourceQuery,
} from '../services/resources';

export function useResources() {
  const route = useRoute();
  const router = useRouter();
  const notify = useNotify();

  const query = computed<ResourceQuery>(() => queryFromRoute(route.query));
  const filtered = computed(() => isFiltered(query.value));

  const resources = shallowRef<LibraryResource[]>([]);
  const lastPage = ref(1);
  const total = ref(0);
  const from = ref(0);
  const to = ref(0);
  const loading = ref(false);
  const loaded = ref(false);
  const filters = shallowRef<ResourceFilters | null>(null);

  let controller: AbortController | null = null;

  async function load(): Promise<void> {
    controller?.abort();
    const current = new AbortController();
    controller = current;
    loading.value = true;
    try {
      const result = await fetchResources(query.value, current.signal);
      // Page au-delà de la dernière (vieux lien, liste réduite entre-temps) : dernière page
      if (result.meta.total > 0 && result.meta.current_page > result.meta.last_page) {
        goToPage(result.meta.last_page, true);
        return;
      }
      resources.value = result.data;
      lastPage.value = result.meta.last_page;
      total.value = result.meta.total;
      from.value = result.meta.from ?? 0;
      to.value = result.meta.to ?? 0;
      loaded.value = true;
    } catch (error) {
      if (isAxiosError(error) && error.code === 'ERR_CANCELED') return;
      notify.error(extractApiError(error, 'La bibliothèque n’a pas pu être chargée. Réessayez.'));
    } finally {
      // Une requête annulée ne termine pas le chargement de celle qui l'a remplacée
      if (controller === current) loading.value = false;
    }
  }

  async function loadFilters(): Promise<void> {
    try {
      filters.value = await fetchResourceFilters();
    } catch {
      // Sans les valeurs des filtres, la liste et la recherche restent disponibles
      filters.value = null;
    }
  }

  /** Remplace la recherche dans l'URL (sans nouvelle entrée d'historique) et revient à la page 1. */
  function update(changes: Partial<ResourceQuery>): void {
    void router.replace({ query: routeFromQuery({ ...query.value, page: 1, ...changes }) });
  }

  /** Change de page : nouvelle entrée d'historique, le bouton retour revient à la précédente. */
  function goToPage(page: number, replace = false): void {
    const location = { query: routeFromQuery({ ...query.value, page }) };
    void (replace ? router.replace(location) : router.push(location));
  }

  /** Nouvelle taille de page : on reste sur la page qui contient la première ressource affichée. */
  function setPerPage(perPage: PerPage): void {
    const page = Math.floor((Math.max(from.value, 1) - 1) / perPage) + 1;
    void router.replace({ query: routeFromQuery({ ...query.value, page, perPage }) });
  }

  /** Efface la recherche, les filtres et la date ; garde le tri et la taille de page. */
  function reset(): void {
    const { sort, perPage } = query.value;
    void router.replace({ query: routeFromQuery({ ...queryFromRoute({}), sort, perPage }) });
  }

  /** Signet : affiché tout de suite, annulé si l'API refuse. */
  async function toggleSaved(resource: LibraryResource): Promise<void> {
    const saved = !resource.is_saved;
    replaceResource(resource.id, { is_saved: saved });
    try {
      await setSaved(resource.id, saved);
    } catch (error) {
      replaceResource(resource.id, { is_saved: !saved });
      notify.error(extractApiError(error, 'Le signet n’a pas pu être enregistré. Réessayez.'));
    }
  }

  function replaceResource(id: number, changes: Partial<LibraryResource>): void {
    resources.value = resources.value.map((item) =>
      item.id === id ? { ...item, ...changes } : item
    );
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

  const activeDate = computed(() =>
    query.value.date ? optionLabel(filters.value?.date ?? [], query.value.date) : ''
  );

  // Nouvelle recherche ou nouvelle page dans l'URL : la liste est rechargée
  const routeName = route.name;
  watch(
    () => route.query,
    () => {
      if (route.name === routeName) void load();
    },
    { deep: true }
  );

  onBeforeUnmount(() => controller?.abort());

  return {
    query,
    filtered,
    resources,
    page: computed(() => query.value.page),
    lastPage,
    total,
    from,
    to,
    loading,
    loaded,
    filters,
    activeFilters,
    activeDate,
    update,
    goToPage,
    setPerPage,
    reset,
    toggleSaved,
    start: () => Promise.all([load(), loadFilters()]),
  };
}
