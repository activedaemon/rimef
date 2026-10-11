// État de l'annuaire : la recherche et la page vivent dans l'URL (lien partageable,
// bouton retour d'une page à l'autre) ; chaque page remplace la précédente.
import { isAxiosError } from 'axios';
import { computed, onBeforeUnmount, ref, shallowRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { useNotify } from '@/composables/useNotify';
import { extractApiError } from '@/lib/http';
import {
  FILTER_KEYS,
  fetchMemberFilters,
  fetchMembers,
  setFavorite,
  isFiltered,
  queryFromRoute,
  routeFromQuery,
  type DirectoryQuery,
  type FilterOption,
  type Member,
  type MemberFilters,
  type PerPage,
} from '../services/members';

export function useMemberDirectory() {
  const route = useRoute();
  const router = useRouter();
  const notify = useNotify();

  const query = computed<DirectoryQuery>(() => queryFromRoute(route.query));
  const filtered = computed(() => isFiltered(query.value));

  const members = shallowRef<Member[]>([]);
  const lastPage = ref(1);
  const total = ref(0);
  const from = ref(0);
  const to = ref(0);
  const directoryTotal = ref(0);
  const loading = ref(false);
  const loaded = ref(false);
  const filters = shallowRef<MemberFilters | null>(null);

  let controller: AbortController | null = null;

  async function load(): Promise<void> {
    controller?.abort();
    const current = new AbortController();
    controller = current;
    loading.value = true;
    try {
      const result = await fetchMembers(query.value, current.signal);
      // Page au-delà de la dernière (vieux lien, liste réduite entre-temps) : dernière page
      if (result.meta.total > 0 && result.meta.current_page > result.meta.last_page) {
        goToPage(result.meta.last_page, true);
        return;
      }
      members.value = result.data;
      lastPage.value = result.meta.last_page;
      total.value = result.meta.total;
      from.value = result.meta.from ?? 0;
      to.value = result.meta.to ?? 0;
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

  /** Remplace la recherche dans l'URL (sans nouvelle entrée d'historique) et revient à la page 1. */
  function update(changes: Partial<DirectoryQuery>): void {
    void router.replace({ query: routeFromQuery({ ...query.value, page: 1, ...changes }) });
  }

  /** Change de page : nouvelle entrée d'historique, le bouton retour revient à la précédente. */
  function goToPage(page: number, replace = false): void {
    const location = { query: routeFromQuery({ ...query.value, page }) };
    void (replace ? router.replace(location) : router.push(location));
  }

  /** Nouvelle taille de page : on reste sur la page qui contient la première médiatrice affichée. */
  function setPerPage(perPage: PerPage): void {
    const page = Math.floor((Math.max(from.value, 1) - 1) / perPage) + 1;
    void router.replace({ query: routeFromQuery({ ...query.value, page, perPage }) });
  }

  /** Marque-page : affiché tout de suite, annulé si l'API refuse. */
  async function toggleFavorite(member: Member): Promise<void> {
    const favorite = !member.is_favorite;
    replaceMember(member.id, { is_favorite: favorite });
    try {
      await setFavorite(member.id, favorite);
    } catch (error) {
      replaceMember(member.id, { is_favorite: !favorite });
      notify.error(extractApiError(error, 'Le favori n’a pas pu être enregistré. Réessayez.'));
    }
  }

  function replaceMember(id: number, changes: Partial<Member>): void {
    members.value = members.value.map((item) => (item.id === id ? { ...item, ...changes } : item));
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
    members,
    page: computed(() => query.value.page),
    lastPage,
    total,
    from,
    to,
    directoryTotal,
    loading,
    loaded,
    filters,
    activeFilters,
    update,
    reset,
    toggleFavorite,
    goToPage,
    setPerPage,
    start: () => Promise.all([load(), loadFilters()]),
  };
}
