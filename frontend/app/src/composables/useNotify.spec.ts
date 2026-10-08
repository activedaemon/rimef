// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';

import { mountApp } from '@/testing/mount-app';
import { useNotify } from './useNotify';

async function mountNotify() {
  let notify!: ReturnType<typeof useNotify>;
  const Host = defineComponent({
    setup() {
      notify = useNotify();
      return () => h('div');
    },
  });
  const { wrapper } = await mountApp(Host);
  return { notify, wrapper };
}

// Les notifications restent affichées d'un test à l'autre : on les retrouve par leur message
function notificationWith(message: string): HTMLElement {
  const notification = [...document.body.querySelectorAll<HTMLElement>('.q-notification')].find(
    (element) => element.textContent?.includes(message)
  );
  if (!notification) {
    throw new Error(`Aucune notification « ${message} »`);
  }
  return notification;
}

describe('useNotify', () => {
  it.each([
    ['success', 'bg-positive', 'ti-circle-check'],
    ['info', 'bg-info', 'ti-info-circle'],
    ['error', 'bg-negative', 'ti-alert-circle'],
  ] as const)('shows a %s notification in the charter colour', async (level, color, icon) => {
    const { notify } = await mountNotify();

    notify[level](`Message ${level}`);
    await flushPromises();

    const notification = notificationWith(`Message ${level}`);
    expect(notification.classList).toContain(color);
    expect(notification.classList).toContain('text-white');
    expect(notification.querySelector(`.${icon}`)).not.toBeNull();
  });

  it('lets the reader close an error, but not a success', async () => {
    const { notify } = await mountNotify();

    notify.success('Enregistré');
    await flushPromises();
    expect(notificationWith('Enregistré').querySelector('button')).toBeNull();

    notify.error('Échec');
    await flushPromises();
    expect(notificationWith('Échec').querySelector('button')?.textContent).toContain('Fermer');
  });
});
