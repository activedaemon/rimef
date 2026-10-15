<script setup lang="ts">
// Section Photo : portrait orné de deux feuilles, choix d'un fichier puis recadrage dans le cercle
// (envoyé à l'enregistrement), retour à la photo actuelle, retrait. Format, poids et taille
// minimale contrôlés sur l'image d'origine, avant le recadrage.
import { defineAsyncComponent, onBeforeUnmount, ref, watch } from 'vue';
import type { QFile, QRejectedEntry } from 'quasar';

import AppButton from '@/components/AppButton.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import { LEAF_PATH, leafTransform } from '@/lib/leaf';

const PhotoCropDialog = defineAsyncComponent(() => import('./PhotoCropDialog.vue'));

const MAX_BYTES = 5 * 1024 * 1024;
const MIN_SIDE = 400;
const ACCEPT = '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp';

const props = defineProps<{
  name: string;
  photo: string | null;
  /** Une photo a été choisie ou retirée, pas encore enregistrée. */
  changed: boolean;
  /** Une photo est enregistrée (retrait possible). */
  hasSaved: boolean;
  error?: string;
}>();
const emit = defineEmits<{ choose: [file: File]; revert: []; remove: [] }>();

const picker = ref<QFile | null>(null);
const file = ref<File | null>(null);
const rejection = ref('');

// Image d'origine en cours de recadrage (gardée pour « Recadrer » tant que rien n'est enregistré)
const source = ref<{ url: string; minSide: number } | null>(null);
const cropping = ref(false);

function forgetSource(): void {
  if (source.value) URL.revokeObjectURL(source.value.url);
  source.value = null;
}

/** Plus petit côté de l'image, null si elle est illisible. */
function minSideOf(url: string): Promise<number | null> {
  return new Promise((resolve) => {
    const image = new Image();
    image.onload = () => resolve(Math.min(image.naturalWidth, image.naturalHeight));
    image.onerror = () => resolve(null);
    image.src = url;
  });
}

async function onPicked(value: File | null): Promise<void> {
  rejection.value = '';
  file.value = null;
  if (!value) return;

  const url = URL.createObjectURL(value);
  const minSide = await minSideOf(url);
  if (minSide === null || minSide < MIN_SIDE) {
    URL.revokeObjectURL(url);
    rejection.value =
      minSide === null
        ? 'Cette image ne peut pas être lue.'
        : `Cette photo est trop petite : ${MIN_SIDE} × ${MIN_SIDE} px minimum.`;
    return;
  }
  forgetSource();
  source.value = { url, minSide };
  cropping.value = true;
}

function onCropped(cropped: File): void {
  emit('choose', cropped);
}

function revert(): void {
  forgetSource();
  emit('revert');
}

// Photo enregistrée ou abandonnée : l'image d'origine ne sert plus
watch(
  () => props.changed,
  (changed) => {
    if (!changed) forgetSource();
  }
);
onBeforeUnmount(forgetSource);

function onRejected(entries: QRejectedEntry[]): void {
  rejection.value =
    entries[0]?.failedPropValidation === 'max-file-size'
      ? 'Cette photo dépasse 5 Mo.'
      : 'Choisissez une image JPG, PNG ou WebP.';
}
</script>

<template>
  <div class="photo-field">
    <div class="photo-field__portrait">
      <MemberAvatar :name="name" :photo="photo" :size="124" />
      <svg class="photo-field__leaves" viewBox="0 0 90 72" aria-hidden="true">
        <path
          :d="LEAF_PATH"
          :transform="leafTransform(8, 66, -38, 64, 22)"
          fill="#C86F55"
          opacity="0.9"
        />
        <path
          :d="LEAF_PATH"
          :transform="leafTransform(6, 68, -78, 48, 17)"
          fill="#8FA89C"
          opacity="0.9"
        />
      </svg>
    </div>

    <div class="photo-field__controls">
      <div class="photo-field__buttons">
        <AppButton
          variant="outline"
          icon="camera"
          label="Modifier la photo"
          @click="picker?.pickFiles()"
        />
        <q-btn
          v-if="changed && source"
          flat
          no-caps
          label="Recadrer"
          class="link-more"
          @click="cropping = true"
        />
        <q-btn
          v-if="changed"
          flat
          no-caps
          label="Revenir à la photo actuelle"
          class="link-more"
          @click="revert"
        />
        <q-btn
          v-else-if="hasSaved"
          flat
          no-caps
          label="Retirer la photo"
          class="link-more"
          @click="emit('remove')"
        />
      </div>
      <p class="photo-field__hint">
        <b>JPG, PNG ou WebP</b>, 5 Mo maximum. Format carré conseillé, 400 × 400 px minimum, visage
        centré.
      </p>
      <p v-if="rejection || error" class="photo-field__error" role="alert">
        {{ rejection || error }}
      </p>
      <q-file
        ref="picker"
        v-model="file"
        :accept="ACCEPT"
        :max-file-size="MAX_BYTES"
        class="photo-field__input"
        aria-label="Choisir une photo"
        @update:model-value="onPicked"
        @rejected="onRejected"
      />
      <PhotoCropDialog
        v-if="source"
        v-model="cropping"
        :src="source.url"
        :min-side="source.minSide"
        @crop="onCropped"
      />
    </div>
  </div>
</template>

<style lang="scss" scoped>
.photo-field {
  display: flex;
  align-items: center;
  gap: 30px;

  &__portrait {
    position: relative;
    flex: none;

    :deep(.member-avatar) {
      box-shadow:
        0 0 0 4px var(--paper),
        0 0 0 5px var(--border);
    }
  }

  &__leaves {
    position: absolute;
    bottom: -6px;
    left: -20px;
    width: 56px;
    height: 46px;
    pointer-events: none;
  }

  &__controls {
    display: grid;
    gap: 10px;
    justify-items: start;
  }

  &__buttons {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 16px;
  }

  &__hint {
    font-size: var(--fs-xs);
    line-height: 1.6;
    color: var(--muted);

    b {
      font-weight: 500;
      color: var(--ink-2);
    }
  }

  &__error {
    font-size: var(--fs-xs);
    color: var(--terracotta-ink);
  }

  // Champ fichier invisible, ouvert par « Modifier la photo »
  &__input {
    display: none;
  }
}

@media (max-width: 719px) {
  .photo-field {
    flex-direction: column;
    align-items: flex-start;
    gap: 20px;
  }
}
</style>
