<script setup lang="ts">
// Repères de la fiche (fiche KeyFigures) : années d'expérience, pays d'intervention, publics
// accompagnés. Seuls les repères renseignés sont affichés ; aucun → rien.
import { computed } from 'vue';

import type { MemberProfile } from '../services/members';

const props = defineProps<{ profile: MemberProfile }>();

const years = computed(() => props.profile.years_of_experience);
const countries = computed(() => props.profile.zones.length);
const visible = computed(
  () => years.value !== null || countries.value > 0 || props.profile.audiences
);
</script>

<template>
  <ul v-if="visible" class="profile-marks" aria-label="Repères">
    <li v-if="years !== null">
      <b>
        {{ years }}
        <small>{{ years > 1 ? 'ans' : 'an' }}</small>
      </b>
      <span>d’expérience en médiation</span>
    </li>
    <li v-if="countries > 0">
      <b>{{ countries }}</b>
      <span>pays d’intervention</span>
    </li>
    <li v-if="profile.audiences">
      <p class="profile-marks__text">{{ profile.audiences }}</p>
      <span>publics accompagnés</span>
    </li>
  </ul>
</template>

<style lang="scss" scoped>
.profile-marks {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  margin: 0;
  padding: 0;
  border-top: 1px solid var(--border);
  list-style: none;

  li {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 16px 20px 18px;
    border-left: 1px solid var(--border);

    &:first-child {
      padding-left: 0;
      border-left: 0;
    }
  }

  b {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.9rem;
    font-weight: 400;
    font-variant-numeric: tabular-nums;
    line-height: 1;
    color: var(--petrol);

    small {
      // noinspection CssNoGenericFontName
      font-family: var(--font-ui);
      font-size: var(--fs-sm);
      color: var(--ink-2);
    }
  }

  span {
    font-size: var(--fs-xs);
    color: var(--muted);
  }

  &__text {
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.2rem;
    line-height: 1.2;
    color: var(--petrol);
  }
}

// Téléphone : deux colonnes, le texte des publics sur toute la largeur
@media (max-width: 719px) {
  .profile-marks {
    grid-template-columns: 1fr 1fr;

    li {
      padding: 14px 0 14px 16px;

      &:nth-child(odd) {
        padding-left: 0;
        border-left: 0;
      }

      &:nth-child(n + 3) {
        border-top: 1px solid var(--border);
      }

      // Repère seul sur sa ligne : toute la largeur
      &:last-child:nth-child(odd) {
        grid-column: 1 / -1;
      }
    }

    b {
      font-size: 1.6rem;
    }
  }
}
</style>
