// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';

import { mountApp } from '@/testing/mount-app';
import PhotoCropDialog from './PhotoCropDialog.vue';

// Faux recadreur : happy-dom ne dessine pas de canvas
const cropper = vi.hoisted(() => ({
  zoom: vi.fn(),
  move: vi.fn(),
  reset: vi.fn(),
  toBlob: vi.fn((callback: (blob: Blob | null) => void) =>
    callback(new Blob(['webp'], { type: 'image/webp' }))
  ),
}));

vi.mock('vue-advanced-cropper', () => ({
  CircleStencil: {},
  Cropper: defineComponent({
    props: { src: { type: String, required: true } },
    setup(props, { expose }) {
      expose({
        zoom: cropper.zoom,
        move: cropper.move,
        reset: cropper.reset,
        getResult: () => ({ canvas: { toBlob: cropper.toBlob } }),
      });
      return () => h('div', { class: 'fake-cropper', 'data-src': props.src });
    },
  }),
}));
vi.mock('vue-advanced-cropper/dist/style.css', () => ({}));

async function openDialog(minSide = 1200) {
  const { wrapper } = await mountApp(PhotoCropDialog, {
    props: { modelValue: true, src: 'blob:photo', minSide },
  });
  await flushPromises();
  return wrapper;
}

function button(label: string): HTMLElement {
  return Array.from(document.body.querySelectorAll<HTMLElement>('.crop-dialog button')).find(
    (element) =>
      element.textContent?.trim() === label || element.getAttribute('aria-label') === label
  )!;
}

describe('PhotoCropDialog', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  it('crops the chosen image into an 800 px webp file', async () => {
    const wrapper = await openDialog();

    expect(document.body.querySelector('.fake-cropper')?.getAttribute('data-src')).toBe(
      'blob:photo'
    );
    button('Valider').click();
    await flushPromises();

    const [[file]] = wrapper.emitted('crop') as [[File]];
    expect(file.type).toBe('image/webp');
    expect(cropper.toBlob).toHaveBeenCalledWith(expect.any(Function), 'image/webp', 0.9);
    expect(wrapper.emitted('update:modelValue')).toEqual([[false]]);
    wrapper.unmount();
  });

  it('moves and zooms the image from the keyboard, and recenters it', async () => {
    const wrapper = await openDialog();
    const area = document.body.querySelector<HTMLElement>('.crop-dialog__area')!;

    area.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true }));
    area.dispatchEvent(new KeyboardEvent('keydown', { key: '+', bubbles: true }));
    button('Recentrer').click();

    expect(cropper.move).toHaveBeenCalledWith(-24, 0);
    expect(cropper.zoom).toHaveBeenCalledWith(expect.closeTo(1.1));
    expect(cropper.reset).toHaveBeenCalled();
    wrapper.unmount();
  });

  it('does not zoom when the image is just big enough', async () => {
    const wrapper = await openDialog(400);

    expect(button('Zoomer').hasAttribute('disabled')).toBe(true);
    wrapper.unmount();
  });
});
