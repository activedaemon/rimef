// Liste des comptes de l'administration : recherche, filtres, tri et page dans l'URL (lien
// partageable, bouton retour d'une page à l'autre), comme l'annuaire Réseau.
import { isAxiosError } from 'axios';
import { computed, onBeforeUnmount, ref, shallowRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { optionLabel } from '@/lib/filters';
import {
  FILTER_KEYS,
  fetchAccounts,
  isFiltered,
  queryFromRoute,
  routeFromQuery,
  type Account,
  type AccountFacets,
  type AccountQuery,
  type PerPage,
} from '../services/accounts';

export function useAccountDirectory() {
  const route = useRoute();
  const router = useRouter();

  const query = computed<AccountQuery>(() => queryFromRoute(route.query));
  const filtered = computed(() => isFiltered(query.value));

  const accounts = shallowRef<Account[]>([]);
  const facets = shallowRef<AccountFacets | null>(null);
  const lastPage = ref(1);
  const total = ref(0);
  const from = ref(0);
  const to = ref(0);
  const loading = ref(false);
  const loaded = ref(false);
  const failed = ref(false);

  let controller: AbortController | null = null;

  async function load(): Promise<void> {
    controller?.abort();
    const current = new AbortController();
    controller = current;
    loading.value = true;
    failed.value = false;
    try {
      const result = await fetchAccounts(query.value, current.signal);
      if (result.meta.total > 0 && result.meta.current_page > result.meta.last_page) {
        goToPage(result.meta.last_page, true);
        return;
      }
      accounts.value = result.data;
      facets.value = result.meta.facets;
      lastPage.value = result.meta.last_page;
      total.value = result.meta.total;
      from.value = result.meta.from ?? 0;
      to.value = result.meta.to ?? 0;
      loaded.value = true;
    } catch (error) {
      if (isAxiosError(error) && error.code === 'ERR_CANCELED') return;
      failed.value = true;
    } finally {
      if (controller === current) loading.value = false;
    }
  }

  function update(changes: Partial<AccountQuery>): void {
    void router.replace({ query: routeFromQuery({ ...query.value, page: 1, ...changes }) });
  }

  function goToPage(page: number, replace = false): void {
    const location = { query: routeFromQuery({ ...query.value, page }) };
    void (replace ? router.replace(location) : router.push(location));
  }

  function setPerPage(perPage: PerPage): void {
    const page = Math.floor((Math.max(from.value, 1) - 1) / perPage) + 1;
    void router.replace({ query: routeFromQuery({ ...query.value, page, perPage }) });
  }

  function reset(): void {
    void router.replace({ query: {} });
  }

  /** Compte modifié par une action : remplacé sur place (la liste n'est pas rechargée). */
  function replace(account: Account): void {
    accounts.value = accounts.value.map((item) => (item.id === account.id ? account : item));
  }

  const activeFilters = computed(() =>
    FILTER_KEYS.flatMap((key) =>
      query.value.selected[key].map((value) => ({
        key,
        value,
        label: optionLabel(facets.value?.[key] ?? [], value),
      }))
    )
  );

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
    accounts,
    facets,
    page: computed(() => query.value.page),
    lastPage,
    total,
    from,
    to,
    loading,
    loaded,
    failed,
    activeFilters,
    update,
    reset,
    goToPage,
    setPerPage,
    replace,
    load,
  };
}
