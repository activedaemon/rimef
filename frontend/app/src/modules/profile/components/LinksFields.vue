<script setup lang="ts">
// Section Liens : LinkedIn, site personnel, organisation et un lien libre avec son intitulé.
import type { ProfileLinks } from '../services/profile';

defineProps<{ errors: Record<string, string> }>();
const links = defineModel<ProfileLinks>({ required: true });
</script>

<template>
  <div class="links-fields">
    <div>
      <label class="field-label" for="link-linkedin">LinkedIn</label>
      <q-input
        v-model="links.linkedin"
        for="link-linkedin"
        type="url"
        outlined
        dense
        placeholder="https://www.linkedin.com/in/…"
        :error="!!errors['links.linkedin']"
        :error-message="errors['links.linkedin']"
      >
        <template #prepend><q-icon name="brand-linkedin" size="18px" /></template>
      </q-input>
    </div>
    <div>
      <label class="field-label" for="link-website">Site personnel</label>
      <q-input
        v-model="links.website"
        for="link-website"
        type="url"
        outlined
        dense
        placeholder="https://"
        :error="!!errors['links.website']"
        :error-message="errors['links.website']"
      >
        <template #prepend><q-icon name="world" size="18px" /></template>
      </q-input>
    </div>
    <div>
      <label class="field-label" for="link-organization">Organisation</label>
      <q-input
        v-model="links.organization"
        for="link-organization"
        type="url"
        outlined
        dense
        placeholder="https://"
        :error="!!errors['links.organization']"
        :error-message="errors['links.organization']"
      >
        <template #prepend><q-icon name="building" size="18px" /></template>
      </q-input>
    </div>
    <fieldset class="links-fields__other">
      <legend class="field-label">Autre lien</legend>
      <q-input
        v-model="links.other.label"
        outlined
        dense
        maxlength="40"
        aria-label="Intitulé de l’autre lien"
        placeholder="Intitulé, ex. Podcast"
        :error="!!errors['links.other.label']"
        :error-message="errors['links.other.label']"
      />
      <q-input
        v-model="links.other.url"
        type="url"
        outlined
        dense
        aria-label="Adresse de l’autre lien"
        placeholder="https://"
        :error="!!errors['links.other.url']"
        :error-message="errors['links.other.url']"
      />
    </fieldset>
  </div>
</template>

<style lang="scss" scoped>
.links-fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 6px 20px;

  &__other {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 3fr);
    gap: 0 12px;
    grid-column: 1 / -1;
    margin: 0;
    padding: 0;
    border: 0;

    legend {
      grid-column: 1 / -1;
      padding: 0;
    }
  }
}

@media (max-width: 719px) {
  .links-fields,
  .links-fields__other {
    grid-template-columns: 1fr;
  }
}
</style>
