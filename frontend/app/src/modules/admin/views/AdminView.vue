<script setup lang="ts">
// Rubrique Administration, onglet Membres (maquette Administration) : intro, rubriques, filtres
// Statut et Rôle appliqués aussitôt, « Inviter une personne », compteur, tri, liste des comptes,
// pagination. La recherche par nom ou email passe par le champ du bandeau (meta.searchInPage).
import { type ComponentPublicInstance, computed, onMounted, ref } from 'vue';

import AppButton from '@/components/AppButton.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterMenu from '@/components/FilterMenu.vue';
import ActiveFilters from '@/components/ActiveFilters.vue';
import FilterTag from '@/components/FilterTag.vue';
import ListPager from '@/components/ListPager.vue';
import PageIntro from '@/components/PageIntro.vue';
import ResultsBar from '@/components/ResultsBar.vue';
import AccountsTable from '../components/AccountsTable.vue';
import AdminTabs from '../components/AdminTabs.vue';
import InviteDialog from '../components/InviteDialog.vue';
import { useAccountDirectory } from '../composables/useAccountDirectory';
import {
  FILTER_KEYS,
  FILTER_LABELS,
  PER_PAGE_OPTIONS,
  SORT_OPTIONS,
  type FilterKey,
} from '../services/accounts';

const directory = useAccountDirectory();
const { query } = directory;

function setSelection(key: FilterKey, values: string[]): void {
  directory.update({ selected: { ...query.value.selected, [key]: values } });
}

function removeFilter(key: FilterKey, value: string): void {
  setSelection(
    key,
    query.value.selected[key].filter((item) => item !== value)
  );
}

const sort = computed({
  get: () => query.value.sort,
  set: (value) => directory.update({ sort: value }),
});

const pendingCount = computed(
  () => directory.facets.value?.status.find((option) => option.value === 'pending')?.count ?? 0
);
const total = computed(() => directory.facets.value?.total ?? 0);
const onlyPending = computed(
  () =>
    !query.value.q &&
    query.value.selected.role.length === 0 &&
    query.value.selected.status.length === 1 &&
    query.value.selected.status[0] === 'pending'
);

// Invitation
const inviting = ref(false);

const resultsBar = ref<ComponentPublicInstance | null>(null);

function changePage(page: number): void {
  directory.goToPage(page);
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  (resultsBar.value?.$el as HTMLElement | undefined)?.scrollIntoView({
    behavior: reduceMotion ? 'auto' : 'smooth',
    block: 'start',
  });
}

onMounted(() => void directory.load());
</script>

<template>
  <q-page class="inner-page">
    <div class="container">
      <PageIntro title="Administration" lead="Gérez les comptes du réseau et les invitations.">
        <template #aside>
          <div class="admin-intro-actions">
            <AppButton icon="user-plus" label="Inviter une personne" @click="inviting = true" />
          </div>
        </template>
      </PageIntro>
    </div>

    <AdminTabs :count="directory.facets.value ? total : null" />

    <div class="container">
      <div class="admin-filters" role="group" aria-label="Filtres">
        <FilterMenu
          v-for="key in FILTER_KEYS"
          :key="key"
          :label="FILTER_LABELS[key]"
          :options="directory.facets.value?.[key] ?? []"
          :model-value="query.selected[key]"
          @update:model-value="(values: string[]) => setSelection(key, values)"
        />
      </div>

      <ActiveFilters :filtered="directory.filtered.value" @clear="directory.reset">
        <FilterTag
          v-if="query.q"
          :label="`« ${query.q} »`"
          :aria-label="`Retirer la recherche ${query.q}`"
          @click="directory.update({ q: '' })"
        />
        <FilterTag
          v-for="filter in directory.activeFilters.value"
          :key="`${filter.key}-${filter.value}`"
          :label="filter.label"
          :aria-label="`Retirer le filtre ${filter.label}`"
          @click="removeFilter(filter.key, filter.value)"
        />
      </ActiveFilters>

      <ResultsBar
        ref="resultsBar"
        v-model:sort="sort"
        :noun="{ one: 'compte', many: 'comptes' }"
        :from="directory.from.value"
        :to="directory.to.value"
        :total="directory.total.value"
        :last-page="directory.lastPage.value"
        :filtered="directory.filtered.value"
        :overall="total"
        :sort-options="SORT_OPTIONS"
      >
        <template v-if="!directory.filtered.value && pendingCount > 0" #actions>
          <q-btn
            flat
            dense
            no-caps
            class="link-more"
            :label="`${pendingCount} ${pendingCount > 1 ? 'invitations' : 'invitation'} en attente`"
            @click="setSelection('status', ['pending'])"
          />
        </template>
      </ResultsBar>

      <div v-if="!directory.loaded.value && !directory.failed.value" class="admin-loading">
        <q-spinner color="primary" size="32px" />
        <span class="sr-only">Chargement des comptes…</span>
      </div>

      <EmptyState
        v-else-if="directory.failed.value"
        icon="alert-circle"
        title="La liste des comptes n’a pas pu être chargée"
        text="La connexion a été interrompue. Vérifiez votre connexion puis réessayez."
        class="admin-empty"
      >
        <AppButton variant="outline" label="Réessayer" @click="directory.load" />
      </EmptyState>

      <EmptyState
        v-else-if="!directory.accounts.value.length && onlyPending"
        icon="mail-check"
        title="Aucune invitation en attente"
        text="Toutes les personnes invitées ont activé leur compte."
        class="admin-empty"
      >
        <AppButton icon="user-plus" label="Inviter une personne" @click="inviting = true" />
      </EmptyState>

      <EmptyState
        v-else-if="!directory.accounts.value.length"
        icon="users"
        title="Aucun compte ne correspond"
        :text="
          query.q
            ? 'Vérifiez l’orthographe du nom ou de l’email, ou retirez un filtre.'
            : 'Retirez un filtre pour élargir la liste.'
        "
        class="admin-empty"
      >
        <AppButton variant="outline" label="Retirer les filtres" @click="directory.reset" />
      </EmptyState>

      <template v-else>
        <AccountsTable :accounts="directory.accounts.value" :busy="directory.loading.value" />
        <ListPager
          items-label="comptes"
          :total="directory.total.value"
          :page="directory.page.value"
          :last-page="directory.lastPage.value"
          :per-page="query.perPage"
          :per-page-options="PER_PAGE_OPTIONS"
          @update:page="changePage"
          @update:per-page="directory.setPerPage"
        />
      </template>
    </div>

    <InviteDialog v-model="inviting" @invited="() => directory.load()" />
  </q-page>
</template>

<style lang="scss" scoped>
.admin-intro-actions {
  // Colonne d'actions de la fiche d'une médiatrice : bouton de 250 px en haut à droite
  display: flex;
  align-items: flex-start;
  justify-content: flex-end;
  padding-top: 14px;

  .q-btn {
    width: 250px;
  }
}

.admin-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: var(--s-5);
}

.admin-loading {
  display: grid;
  place-items: center;
  padding-block: var(--s-16);
}

.admin-empty {
  margin-top: 20px;
}

@media (max-width: 719px) {
  // Comme les actions de la fiche : sous le texte, sur toute la largeur
  .admin-intro-actions {
    padding-top: 0;

    .q-btn {
      flex: 1;
      width: auto;
    }
  }
}
</style>
