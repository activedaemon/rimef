// État de l'annuaire : la recherche vit dans l'URL (lien partageable, bouton retour),
// les pages s'ajoutent les unes aux autres avec « Afficher plus ».
import { isAxiosError } from 'axios';
import { computed, onBeforeUnmount, ref, shallowRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import {
  FILTER_KEYS,
  fetchMemberFilters,
  fetchMembers,
  isFiltered,
  queryFromRoute,
  routeFromQuery,
  type DirectoryQuery,
  type FilterOption,
  type Member,
  type MemberFilters,
} from '../services/members';

export function useMemberDirectory() {
  const route = useRoute();
  const router = useRouter();
  const notify = useNotify();

  const query = computed<DirectoryQuery>(() => queryFromRoute(route.query));
  const filtered = computed(() => isFiltered(query.value));

  const members = shallowRef<Member[]>([]);
  const page = ref(0);
  const lastPage = ref(1);
  const total = ref(0);
  const directoryTotal = ref(0);
  const loading = ref(false);
  const loaded = ref(false);
  const filters = shallowRef<MemberFilters | null>(null);

  const hasMore = computed(() => page.value < lastPage.value);

  let controller: AbortController | null = null;

  async function load(nextPage: number): Promise<void> {
    controller?.abort();
    const current = new AbortController();
    controller = current;
    loading.value = true;
    try {
      const result = await fetchMembers(query.value, nextPage, current.signal);
      members.value = nextPage === 1 ? result.data : [...members.value, ...result.data];
      page.value = result.meta.current_page;
      lastPage.value = result.meta.last_page;
      total.value = result.meta.total;
      directoryTotal.value = result.meta.directory_total;
      loaded.value = true;
    } catch (error) {
      if (isAxiosError(error) && error.code === 'ERR_CANCELED') return;
      notify.error(extractApiError(error, 'L’annuaire n’a pas pu être chargé. Réessayez.'));
    } finally {
      // Une requête annulée ne termine pas le chargement de celle qui l'a remplacée
      if (controller === current) loading.value = false;
    }
  }

  async function loadFilters(): Promise<void> {
    try {
      filters.value = await fetchMemberFilters();
    } catch {
      // Sans les valeurs des filtres, la recherche par texte reste disponible
      filters.value = null;
    }
  }

  /** Remplace la recherche dans l'URL (sans nouvelle entrée d'historique). */
  function update(changes: Partial<DirectoryQuery>): void {
    void router.replace({ query: routeFromQuery({ ...query.value, ...changes }) });
  }

  function reset(): void {
    void router.replace({ query: {} });
  }

  /** Valeur cochée et son libellé, pour les filtres actifs retirables. */
  const activeFilters = computed(() =>
    FILTER_KEYS.flatMap((key) =>
      query.value.selected[key].map((value) => ({
        key,
        value,
        label: optionLabel(filters.value?.[key] ?? [], value),
      }))
    )
  );

  function optionLabel(options: FilterOption[], value: string): string {
    return options.find((option) => option.value === value)?.label ?? value;
  }

  // Nouvelle recherche dans l'URL : la liste repart de la première page
  const routeName = route.name;
  watch(
    () => route.query,
    () => {
      if (route.name === routeName) void load(1);
    },
    { deep: true }
  );

  onBeforeUnmount(() => controller?.abort());

  return {
    query,
    filtered,
    members,
    total,
    directoryTotal,
    loading,
    loaded,
    hasMore,
    filters,
    activeFilters,
    update,
    reset,
    loadMore: () => load(page.value + 1),
    start: () => Promise.all([load(1), loadFilters()]),
  };
}
