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
  <q-page class="home">
    <section class="container home__intro">
      <p class="eyebrow">Réseau International des Femmes Médiatrices Francophones</p>
      <h1>Bienvenue sur RIMEF</h1>
      <p class="home__lead">
        L’espace des membres ouvre bientôt : réseau, agenda des rencontres et bibliothèque de
        ressources.
      </p>
    </section>

    <section class="container" aria-labelledby="health-title">
      <q-card flat bordered class="health">
        <q-card-section class="health__body" aria-live="polite">
          <h2 id="health-title" class="health__title">État de la plateforme</h2>

          <div v-if="loading" class="health__row">
            <q-spinner color="primary" size="24px" />
            <span>Vérification en cours…</span>
          </div>

          <div v-else class="health__row" :class="`health__row--${summary.state}`">
            <q-icon :name="STATE_ICONS[summary.state]" size="24px" />
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
    </section>
  </q-page>
</template>

<style lang="scss" scoped>
.home {
  padding-block: var(--s-10) var(--s-16);
  background: var(--paper);

  &__intro {
    margin-bottom: var(--s-10);
  }

  h1 {
    margin-block: var(--s-3) var(--s-4);
  }

  &__lead {
    max-width: 58ch;
    font-size: var(--fs-lead);
    color: var(--ink-2);
  }
}

.health {
  max-width: 560px;
  background: var(--card);
  border-color: var(--border);
  box-shadow: var(--shadow-card);

  &__body {
    display: grid;
    gap: var(--s-4);
  }

  &__title {
    font-size: var(--fs-h3);
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
