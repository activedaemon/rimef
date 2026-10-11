// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import * as notifications from '@modules/notifications/services/notifications';
import { useInbox } from '@/stores/inbox';
import { mountApp } from '@/testing/mount-app';
import NotificationBell from './NotificationBell.vue';

vi.mock('@modules/notifications/services/notifications', () => ({
  fetchNotifications: vi.fn(),
  fetchUnreadCounts: vi.fn(),
  markNotificationRead: vi.fn(),
  markAllNotificationsRead: vi.fn(),
}));

const NEW_MESSAGE: notifications.AppNotification = {
  id: 'a1',
  title: '2 nouveaux messages de Fatou Ndiaye',
  text: 'Bonjour Aminata…',
  path: '/messages/3',
  photo_url: null,
  sender_name: 'Fatou Ndiaye',
  is_read: false,
  created_at: new Date().toISOString(),
};

describe('NotificationBell', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    document.body.innerHTML = '';
    vi.mocked(notifications.fetchNotifications).mockResolvedValue([{ ...NEW_MESSAGE }]);
    vi.mocked(notifications.markNotificationRead).mockResolvedValue();
    vi.mocked(notifications.fetchUnreadCounts).mockResolvedValue({ notifications: 0, messages: 0 });
  });

  it('shows a dot and announces the unread notifications', async () => {
    const { wrapper } = await mountApp(NotificationBell);
    expect(wrapper.find('button').attributes('aria-label')).toBe('Notifications');
    expect(wrapper.find('.notification-bell__dot').exists()).toBe(false);

    useInbox().unreadNotifications = 3;
    await flushPromises();

    expect(wrapper.find('button').attributes('aria-label')).toBe('Notifications, 3 nouvelles');
    expect(wrapper.find('.notification-bell__dot').exists()).toBe(true);
    wrapper.unmount();
  });

  it('opens a notification: marks it as read and goes to its screen', async () => {
    const { wrapper, router } = await mountApp(NotificationBell);
    await wrapper.find('button').trigger('click');
    await flushPromises();

    const item = document.body.querySelector<HTMLElement>('.notification-item')!;
    expect(item.textContent).toContain('2 nouveaux messages de Fatou Ndiaye');
    item.click();
    await flushPromises();

    expect(notifications.markNotificationRead).toHaveBeenCalledWith('a1');
    expect(router.currentRoute.value.fullPath).toBe('/messages/3');

    wrapper.unmount();
  });

  it('keeps the notifications when everything is marked as read, without the new style', async () => {
    vi.mocked(notifications.markAllNotificationsRead).mockResolvedValue();
    const { wrapper } = await mountApp(NotificationBell);
    await wrapper.find('button').trigger('click');
    await flushPromises();

    Array.from(document.body.querySelectorAll<HTMLButtonElement>('button'))
      .find((button) => button.textContent?.includes('Tout marquer comme lu'))!
      .click();
    await flushPromises();

    expect(notifications.markAllNotificationsRead).toHaveBeenCalled();
    const item = document.body.querySelector('.notification-item')!;
    expect(item.classList).not.toContain('notification-item--new');
    expect(document.body.textContent).not.toContain('Tout marquer comme lu');
    wrapper.unmount();
  });

  it('links to all the messages', async () => {
    const { wrapper, router } = await mountApp(NotificationBell);
    await wrapper.find('button').trigger('click');
    await flushPromises();

    document.body.querySelector<HTMLAnchorElement>('.notification-list__foot')!.click();
    await flushPromises();

    expect(router.currentRoute.value.name).toBe('messages');
    wrapper.unmount();
  });

  it('shows each new notification in bold with a dot and a short excerpt', async () => {
    vi.mocked(notifications.fetchNotifications).mockResolvedValue([
      {
        ...NEW_MESSAGE,
        text: 'Le compte rendu de l’atelier d’Abidjan est prêt, pourriez-vous le relire ?',
      },
    ]);
    const { wrapper } = await mountApp(NotificationBell);
    await wrapper.find('button').trigger('click');
    await flushPromises();

    const item = document.body.querySelector('.notification-item')!;
    expect(item.classList).toContain('notification-item--new');
    expect(item.querySelector('small')!.textContent).toMatch(
      /^« Le compte rendu de l’atelier d’Abidjan est prêt… » · \d{2}:\d{2}$/
    );
    wrapper.unmount();
  });

  it('shows a read notification without bold nor dot', async () => {
    vi.mocked(notifications.fetchNotifications).mockResolvedValue([
      { ...NEW_MESSAGE, is_read: true },
    ]);
    const { wrapper } = await mountApp(NotificationBell);
    await wrapper.find('button').trigger('click');
    await flushPromises();

    expect(document.body.querySelector('.notification-item')!.classList).not.toContain(
      'notification-item--new'
    );
    expect(document.body.textContent).not.toContain('Tout marquer comme lu');
    wrapper.unmount();
  });
});
