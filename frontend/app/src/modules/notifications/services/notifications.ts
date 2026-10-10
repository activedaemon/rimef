// Cloche de l'en-tête : notifications et compteurs de non-lus (GET/POST /api/notifications…).
import { ensureCsrf, http } from '@/lib/http';

export interface AppNotification {
  id: string;
  title: string;
  text: string | null;
  /** Écran à ouvrir dans l'application (ex. /messages/3). */
  path: string | null;
  photo_url: string | null;
  sender_name: string | null;
  is_read: boolean;
  created_at: string;
}

export interface UnreadCounts {
  notifications: number;
  messages: number;
}

export async function fetchNotifications(): Promise<AppNotification[]> {
  const { data } = await http.get<{ data: AppNotification[] }>('/notifications');
  return data.data;
}

export async function fetchUnreadCounts(): Promise<UnreadCounts> {
  const { data } = await http.get<{ data: UnreadCounts }>('/notifications/unread-count');
  return data.data;
}

export async function markNotificationRead(id: string): Promise<void> {
  await ensureCsrf();
  await http.post(`/notifications/${id}/read`);
}

export async function markAllNotificationsRead(): Promise<void> {
  await ensureCsrf();
  await http.post('/notifications/read-all');
}
