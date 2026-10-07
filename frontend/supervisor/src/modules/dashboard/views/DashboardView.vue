<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

import { fetchHealth, summarizeHealth, type HealthResponse } from '../services/health.service';

const loading = ref(true);
const health = ref<HealthResponse | null>(null);

const summary = computed(() => summarizeHealth(health.value));

const STATE_ICONS = {
  ok: 'circle-check',
  degraded: 'alert-triangle',
  unreachable: 'plug-connected-x',
} as const;

async function loadHealth(): Promise<void> {
  loading.value = true;
  try {
    health.value = await fetchHealth();
  } catch {
    health.value = null;
  } finally {
    loading.value = false;
  }
}

onMounted(loadHealth);
</script>

<template>
  <q-page class="dashboard">
    <div class="container">
      <h1 class="dashboard__title">Tableau de bord</h1>
      <p class="dashboard__intro">Gestion des tenants de la plateforme RIMEF.</p>

      <q-card flat bordered class="health" aria-labelledby="health-title">
        <q-card-section class="health__body" aria-live="polite">
          <h2 id="health-title" class="health__title">API centrale</h2>

          <div v-if="loading" class="health__row">
            <q-spinner color="primary" size="22px" />
            <span>Vérification en cours…</span>
          </div>

          <div v-else class="health__row" :class="`health__row--${summary.state}`">
            <q-icon :name="STATE_ICONS[summary.state]" size="22px" />
            <div>
              <p class="health__label">{{ summary.label }}</p>
              <p class="text-muted">{{ summary.detail }}</p>
            </div>
          </div>
        </q-card-section>

        <q-card-actions v-if="!loading && summary.state !== 'ok'" class="health__actions">
          <q-btn outline color="primary" no-caps label="Réessayer" @click="loadHealth" />
        </q-card-actions>
      </q-card>
    </div>
  </q-page>
</template>

<style lang="scss" scoped>
.dashboard {
  padding-block: var(--s-8) var(--s-12);
  background: var(--ivory);

  &__title {
    font-size: var(--fs-h2);
  }

  &__intro {
    margin-block: var(--s-2) var(--s-6);
    color: var(--ink-2);
  }
}

.health {
  max-width: 560px;
  background: var(--card);
  border-color: var(--border);

  &__body {
    display: grid;
    gap: var(--s-3);
  }

  &__title {
    // noinspection CssNoGenericFontName
    font-family: var(--font-ui);
    font-size: var(--fs-body);
    font-weight: 600;
  }

  &__row {
    display: flex;
    align-items: flex-start;
    gap: var(--s-3);

    &--ok {
      color: var(--sage-ink);
    }

    &--degraded {
      color: var(--saffron-ink);
    }

    &--unreachable {
      color: var(--terracotta-ink);
    }
  }

  &__label {
    font-weight: 600;
    color: var(--ink);
  }

  &__actions {
    padding: 0 var(--s-4) var(--s-4);
  }
}
</style>
