<script setup lang="ts">
// Recadrage de la photo (vue-advanced-cropper) : cercle fixe, image déplacée au doigt ou à la souris,
// zoom au pincement, à la molette, au curseur ou au clavier. Validation = carré WebP de 800 px.
// Chargé à la demande (defineAsyncComponent) : la bibliothèque n'alourdit que cette page.
import { useQuasar } from 'quasar';
import { computed, ref } from 'vue';
import { CircleStencil, Cropper, type CropperResult } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

import AppButton from '@/components/AppButton.vue';

/** Côté de l'image envoyée ; le serveur la réduit ensuite à 400 px. */
const OUTPUT_SIZE = 800;
/** Côté minimal de la zone gardée dans l'image d'origine (même seuil que l'API). */
const MIN_SOURCE_SIDE = 400;
const KEY_ZOOM = 1.1;
const SLIDER_STEP = 0.05;

const props = defineProps<{
  src: string;
  /** Plus petit côté de l'image d'origine (px), pour borner le zoom. */
  minSide: number;
}>();
const open = defineModel<boolean>({ required: true });
const emit = defineEmits<{ crop: [file: File] }>();

const $q = useQuasar();
const cropper = ref<InstanceType<typeof Cropper> | null>(null);
const exporting = ref(false);

// Zoom : 1 = image entière dans le cercle ; au-delà, la zone gardée rétrécit jusqu'à 400 px
const maxZoom = computed(() => Math.max(1, props.minSide / MIN_SOURCE_SIDE));
const zoom = ref(1);
let fittedWidth = 0;

function onChange(result: CropperResult): void {
  fittedWidth ||= result.coordinates.width;
  zoom.value = fittedWidth / result.coordinates.width;
}

function zoomTo(value: number | null): void {
  if (value === null || !cropper.value) return;
  const target = Math.min(maxZoom.value, Math.max(1, value));
  cropper.value.zoom(target / zoom.value);
}

function stencilSize({ boundaries }: { boundaries: { width: number; height: number } }) {
  const side = Math.min(boundaries.width, boundaries.height) - 48;
  return { width: side, height: side };
}

function recenter(): void {
  cropper.value?.reset();
}

// Clavier : flèches pour déplacer l'image, + et − pour zoomer
function onKeydown(event: KeyboardEvent): void {
  const step = 24;
  const moves: Record<string, [number, number]> = {
    ArrowLeft: [-step, 0],
    ArrowRight: [step, 0],
    ArrowUp: [0, -step],
    ArrowDown: [0, step],
  };
  const move = moves[event.key];
  if (move) {
    cropper.value?.move(...move);
  } else if (event.key === '+' || event.key === '=') {
    zoomTo(zoom.value * KEY_ZOOM);
  } else if (event.key === '-') {
    zoomTo(zoom.value / KEY_ZOOM);
  } else {
    return;
  }
  event.preventDefault();
}

function validate(): void {
  const canvas = cropper.value?.getResult().canvas;
  if (!canvas) return;
  exporting.value = true;
  canvas.toBlob(
    (blob) => {
      exporting.value = false;
      if (!blob) return;
      emit('crop', new File([blob], 'photo.webp', { type: 'image/webp' }));
      open.value = false;
    },
    'image/webp',
    0.9
  );
}
</script>

<template>
  <q-dialog v-model="open" :maximized="$q.screen.lt.sm" @show="fittedWidth = 0">
    <q-card class="rimef-card crop-dialog" aria-labelledby="crop-title">
      <header class="crop-dialog__head">
        <h2 id="crop-title">Recadrer la photo</h2>
        <q-btn v-close-popup flat round icon="x" aria-label="Fermer sans recadrer" />
      </header>

      <p id="crop-help" class="crop-dialog__help">
        Déplacez la photo pour centrer votre visage dans le cercle, puis ajustez le zoom.
      </p>

      <div
        class="crop-dialog__area"
        tabindex="0"
        role="application"
        aria-roledescription="zone de recadrage"
        aria-describedby="crop-help crop-keys"
        @keydown="onKeydown"
      >
        <Cropper
          ref="cropper"
          :src="src"
          :stencil-component="CircleStencil"
          :stencil-props="{ handlers: {}, movable: false, resizable: false, aspectRatio: 1 }"
          :stencil-size="stencilSize"
          :resize-image="{ adjustStencil: false }"
          :min-width="MIN_SOURCE_SIDE"
          :min-height="MIN_SOURCE_SIDE"
          :canvas="{ width: OUTPUT_SIZE, height: OUTPUT_SIZE }"
          image-restriction="stencil"
          background-class="crop-dialog__background"
          class="crop-dialog__cropper"
          @change="onChange"
        />
      </div>
      <p id="crop-keys" class="sr-only">
        Flèches du clavier pour déplacer la photo, plus et moins pour zoomer.
      </p>

      <div class="crop-dialog__zoom">
        <q-btn
          flat
          round
          icon="zoom-out"
          aria-label="Dézoomer"
          :disable="zoom <= 1"
          @click="zoomTo(zoom / KEY_ZOOM)"
        />
        <q-slider
          :model-value="zoom"
          :min="1"
          :max="maxZoom"
          :step="SLIDER_STEP"
          :disable="maxZoom <= 1"
          color="primary"
          aria-label="Zoom"
          class="crop-dialog__slider"
          @update:model-value="zoomTo"
        />
        <q-btn
          flat
          round
          icon="zoom-in"
          aria-label="Zoomer"
          :disable="zoom >= maxZoom"
          @click="zoomTo(zoom * KEY_ZOOM)"
        />
      </div>

      <footer class="crop-dialog__foot">
        <q-btn flat no-caps label="Recentrer" class="link-more" @click="recenter" />
        <div class="crop-dialog__actions">
          <AppButton v-close-popup variant="quiet" label="Annuler" />
          <AppButton label="Valider" :loading="exporting" @click="validate" />
        </div>
      </footer>
    </q-card>
  </q-dialog>
</template>

<style lang="scss" scoped>
.crop-dialog {
  display: flex;
  flex-direction: column;
  width: min(520px, calc(100vw - 32px));
  max-width: none;
  padding: 20px 24px 24px;

  &__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;

    h2 {
      font-size: 1.45rem;
    }
  }

  &__help {
    margin-top: 4px;
    font-size: var(--fs-sm);
    color: var(--muted);
  }

  &__area {
    margin-top: 16px;
    border-radius: var(--radius-sm);
    overflow: hidden;

    &:focus-visible {
      outline: 2px solid var(--saffron);
      outline-offset: 2px;
    }
  }

  &__cropper {
    height: min(400px, 60vh);
    background: var(--petrol-dark);
  }

  :deep(.crop-dialog__background) {
    background: var(--petrol-dark);
  }

  // Cercle de la charte : filet ivoire, ombre du masque en pétrole sombre
  :deep(.vue-circle-stencil__preview) {
    border: 2px solid var(--on-dark);
  }

  &__zoom {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
  }

  &__slider {
    flex: 1;
  }

  &__foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 12px;
  }

  &__actions {
    display: flex;
    gap: 10px;
    margin-left: auto;
  }
}

// Téléphone : plein écran, la zone de recadrage prend la hauteur disponible
@media (max-width: 719px) {
  .crop-dialog {
    width: 100%;
    padding: 16px var(--gutter) 20px;

    &__area {
      flex: 1;
      min-height: 0;
    }

    &__cropper {
      height: 100%;
    }
  }
}
</style>
