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
});
