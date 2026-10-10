import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import * as notifications from '@modules/notifications/services/notifications';
import { useInbox } from './inbox';

vi.mock('@modules/notifications/services/notifications', () => ({
  fetchUnreadCounts: vi.fn(),
}));

describe('inbox store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.resetAllMocks();
    vi.mocked(notifications.fetchUnreadCounts).mockResolvedValue({ notifications: 2, messages: 5 });
  });

  it('loads the unread counts', async () => {
    const inbox = useInbox();
    await inbox.refresh();

    expect(inbox.unreadNotifications).toBe(2);
    expect(inbox.unreadMessages).toBe(5);
  });

  it('does not ask again within 20 seconds, unless forced', async () => {
    const inbox = useInbox();
    await inbox.refresh();
    await inbox.refresh();
    expect(notifications.fetchUnreadCounts).toHaveBeenCalledTimes(1);

    await inbox.refresh(true);
    expect(notifications.fetchUnreadCounts).toHaveBeenCalledTimes(2);
  });

  it('keeps the previous counts when the request fails', async () => {
    const inbox = useInbox();
    await inbox.refresh();
    vi.mocked(notifications.fetchUnreadCounts).mockRejectedValue(new Error('offline'));

    await inbox.refresh(true);
    expect(inbox.unreadMessages).toBe(5);
  });

  it('forgets the counts on reset (logout)', async () => {
    const inbox = useInbox();
    await inbox.refresh();
    inbox.reset();

    expect(inbox.unreadNotifications).toBe(0);
    expect(inbox.unreadMessages).toBe(0);
  });
});
