<script setup lang="ts">
// En-tête de la fiche (fiche ProfileHeader) : portrait, nom, badge, fonction, lieu, région,
// organisation, citation ; les actions (favori, modifier…) arrivent par le slot « actions ».
import { useQuasar } from 'quasar';
import { computed } from 'vue';

import MemberAvatar from '@/components/MemberAvatar.vue';
import type { MemberProfile } from '../services/members';

const props = defineProps<{ profile: MemberProfile }>();

const $q = useQuasar();
// Portrait : 176 px sur ordinateur, 148 px sur tablette, 92 px sur téléphone (maquette)
const portraitSize = computed(() => ($q.screen.lt.sm ? 92 : $q.screen.lt.md ? 148 : 176));

const place = computed(() =>
  [props.profile.city, props.profile.country?.name].filter(Boolean).join(', ')
);
</script>

<template>
  <section class="profile-header" aria-labelledby="profile-name">
    <div class="profile-header__portrait">
      <MemberAvatar :name="profile.name" :photo="profile.photo_url" :size="portraitSize" />
    </div>

    <div class="profile-header__identity">
      <div class="profile-header__name">
        <h1 id="profile-name">{{ profile.name }}</h1>
        <q-badge class="rimef-badge" color="sage-light" text-color="sage-ink">
          Membre RIMeF
        </q-badge>
      </div>
      <p class="profile-header__role">
        Médiatrice<template v-if="profile.job_title">
          <span aria-hidden="true">·</span><em>{{ profile.job_title }}</em>
        </template>
      </p>

      <ul
        v-if="place || profile.region || profile.organization"
        class="profile-header__facts"
        aria-label="Informations"
      >
        <li v-if="place" class="meta">
          <q-icon name="map-pin" size="15px" aria-hidden="true" />{{ place }}
        </li>
        <li v-if="profile.region" class="meta">
          <q-icon name="world" size="15px" aria-hidden="true" />{{ profile.region }}
        </li>
        <li v-if="profile.organization" class="meta">
          <q-icon name="building" size="15px" aria-hidden="true" />{{ profile.organization }}
        </li>
      </ul>

      <blockquote v-if="profile.tagline" class="profile-header__quote">
        « {{ profile.tagline }} »
      </blockquote>
    </div>

    <div v-if="$slots.actions" class="profile-header__actions">
      <slot name="actions" />
    </div>
  </section>
</template>

<style lang="scss" scoped>
.profile-header {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 36px;
  align-items: start;
  padding-block: 26px 30px;

  &__portrait :deep(.member-avatar) {
    box-shadow:
      0 0 0 5px var(--paper),
      0 0 0 6px var(--border),
      var(--shadow-float);
  }

  &__identity {
    min-width: 0;
    padding-top: 10px;
  }

  &__name {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 14px;

    h1 {
      font-size: var(--fs-h1);
      line-height: 1.02;
      letter-spacing: -0.012em;
    }
  }

  &__role {
    margin-top: 8px;
    font-size: 1.0625rem;
    font-weight: 500;
    color: var(--petrol);

    span {
      margin-inline: 6px;
      font-weight: 400;
      color: var(--muted);
    }

    em {
      font-style: normal;
    }
  }

  &__facts {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 22px;
    margin: 14px 0 0;
    padding: 0;
    list-style: none;
  }

  &__quote {
    max-width: 62ch;
    margin: 20px 0 0;
    padding-left: 18px;
    border-left: 1px solid var(--terracotta);
    // noinspection CssNoGenericFontName
    font-family: var(--font-serif);
    font-size: 1.28rem;
    font-style: italic;
    line-height: 1.35;
    color: var(--ink-2);
  }

  &__actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 250px;
    padding-top: 14px;
  }
}

// Tablette : actions sous l'identité
@media (max-width: 1079px) {
  .profile-header {
    grid-template-columns: auto 1fr;
    gap: 28px;

    &__actions {
      grid-column: 2;
      flex-direction: row;
      width: auto;
      padding-top: 0;
    }
  }
}

// Téléphone : portrait et nom côte à côte, le reste sur toute la largeur
@media (max-width: 719px) {
  .profile-header {
    grid-template-columns: 92px 1fr;
    gap: 16px 18px;
    padding-block: 18px 22px;

    &__portrait {
      grid-row: span 2;
      align-self: center;

      :deep(.member-avatar) {
        box-shadow:
          0 0 0 3px var(--paper),
          0 0 0 4px var(--border);
      }
    }

    &__identity {
      display: contents;
    }

    &__name,
    &__role {
      grid-column: 2;
    }

    &__name {
      align-self: end;
      gap: 6px 10px;

      h1 {
        font-size: 2rem;
      }
    }

    &__role {
      align-self: start;
      margin-top: 0;
      font-size: var(--fs-sm);

      span {
        display: none;
      }

      em {
        display: block;
        font-weight: 400;
        color: var(--ink-2);
      }
    }

    &__facts,
    &__quote,
    &__actions {
      grid-column: 1 / -1;
      margin-top: 0;
    }

    &__facts {
      gap: 6px 18px;
    }

    &__quote {
      font-size: 1.15rem;
    }

    &__actions > * {
      flex: 1;
    }
  }
}
</style>
