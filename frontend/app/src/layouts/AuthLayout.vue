<script setup lang="ts">
import AppLogo from '@/components/brand/AppLogo.vue';
import LeafMotif from '@/components/brand/LeafMotif.vue';
</script>

<template>
  <q-layout view="hHh lpR fff">
    <q-page-container>
      <q-page class="auth-page">
        <main class="auth-page__inner">
          <AppLogo :height="76" :compact-height="0" class="auth-page__logo" />

          <div class="auth-card">
            <LeafMotif class="auth-card__leaf" />
            <router-view />
          </div>

          <p class="auth-page__note">Espace réservé aux membres du réseau.</p>
        </main>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<style lang="scss" scoped>
.auth-page {
  display: flex;
  justify-content: center;
  padding: var(--s-10) var(--gutter) var(--s-12);
  background: var(--ivory);

  &__inner {
    display: grid;
    justify-items: center;
    gap: var(--s-6);
    width: 100%;
    max-width: 420px;
    align-content: start;
  }

  &__note {
    font-size: var(--fs-sm);
    color: var(--muted);
    text-align: center;
  }
}

.auth-card {
  position: relative;
  width: 100%;
  padding: var(--s-8);
  overflow: hidden;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-card);

  &__leaf {
    position: absolute;
    top: 0;
    right: 0;
    width: 96px;
    height: 77px;
    opacity: 0.9;
  }
}

@media (max-width: 720px) {
  .auth-page {
    padding-top: var(--s-8);
  }

  .auth-card {
    padding: var(--s-6) var(--s-5);

    &__leaf {
      width: 72px;
      height: 58px;
    }
  }
}
</style>
