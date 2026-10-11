// Messagerie interne : conversations par paire de membres (GET/POST /api/conversations…)
// et fenêtre « Contacter » des fiches (POST /api/members/{slug}/contact).
import { ensureCsrf, http } from '@/lib/http';
import type { Member } from '@modules/network/services/members';

/** Longueur maximale d'un message (SendMessageRequest::MAX_LENGTH). */
export const MAX_MESSAGE_LENGTH = 2000;

export interface ConversationContact {
  id: number;
  slug: string;
  name: string;
  photo_url: string | null;
  /** « Dakar, Sénégal », null si le profil ne le précise pas. */
  place: string | null;
  /** Fiche visible dans l'annuaire. */
  has_profile: boolean;
}

export interface Conversation {
  id: number;
  /** Interlocutrice (null si son compte a été supprimé). */
  contact: ConversationContact | null;
  last_message: { excerpt: string; sent_at: string; is_mine: boolean } | null;
  unread_count: number;
}

export interface Message {
  id: number;
  /** null pour un message supprimé (« Message supprimé »). */
  body: string | null;
  sent_at: string;
  is_mine: boolean;
  is_edited: boolean;
  is_deleted: boolean;
  /** Modifiable par moi : mon message, envoyé il y a moins de 15 minutes, non supprimé. */
  can_edit: boolean;
}

/** Délai de modification d'un message après son envoi (Message::EDIT_WINDOW_MINUTES). */
export const EDIT_WINDOW_MS = 15 * 60_000;

export interface ConversationPage {
  data: Conversation[];
  meta: {
    current_page: number;
    last_page: number;
    /** Toutes mes conversations, recherche et filtre ignorés. */
    total_conversations: number;
    /** Conversations ayant des messages non lus. */
    unread_conversations: number;
  };
}

/** Filtre de la liste : toutes les conversations ou celles qui ont des non-lus. */
export type ConversationFilter = 'all' | 'unread';

/** Recherche du bandeau (`q`) et filtre « Non lues ». */
export interface ConversationQuery {
  q?: string;
  unread?: boolean;
}

export async function fetchConversations(
  page = 1,
  query: ConversationQuery = {},
  signal?: AbortSignal
): Promise<ConversationPage> {
  const params: Record<string, unknown> = { page };
  if (query.q?.trim()) params.q = query.q.trim();
  if (query.unread) params.unread = 1;
  const { data } = await http.get<ConversationPage>('/conversations', { params, signal });
  return data;
}

export async function fetchConversation(id: number): Promise<Conversation> {
  const { data } = await http.get<{ data: Conversation }>(`/conversations/${id}`);
  return data.data;
}

/** Messages, les plus récents d'abord ; `before` = id du plus ancien déjà affiché. */
export async function fetchMessages(
  id: number,
  before?: number
): Promise<{ messages: Message[]; hasMore: boolean }> {
  const { data } = await http.get<{ data: Message[]; meta: { has_more: boolean } }>(
    `/conversations/${id}/messages`,
    { params: before ? { before } : {} }
  );
  return { messages: data.data, hasMore: data.meta.has_more };
}

export async function markConversationRead(id: number): Promise<void> {
  await ensureCsrf();
  await http.put(`/conversations/${id}/read`);
}

export async function sendMessage(id: number, body: string): Promise<Message> {
  await ensureCsrf();
  const { data } = await http.post<{ data: Message }>(`/conversations/${id}/messages`, { body });
  return data.data;
}

/** Suppression de la conversation pour moi : l'autre participante la garde. */
export async function deleteConversation(id: number): Promise<void> {
  await ensureCsrf();
  await http.delete(`/conversations/${id}`);
}

/** Modification de mon message (15 minutes après l'envoi). */
export async function editMessage(
  conversationId: number,
  id: number,
  body: string
): Promise<Message> {
  await ensureCsrf();
  const { data } = await http.patch<{ data: Message }>(
    `/conversations/${conversationId}/messages/${id}`,
    { body }
  );
  return data.data;
}

/** Suppression de mon message : « Message supprimé » pour les deux participantes. */
export async function deleteMessage(conversationId: number, id: number): Promise<Message> {
  await ensureCsrf();
  const { data } = await http.delete<{ data: Message }>(
    `/conversations/${conversationId}/messages/${id}`
  );
  return data.data;
}

/** Fenêtre « Nouveau message » : médiatrices de l'annuaire dont le nom correspond. */
export async function searchRecipients(q: string, signal?: AbortSignal): Promise<Member[]> {
  const { data } = await http.get<{ data: Member[] }>('/members', {
    params: { q: q.trim() || undefined, sort: 'name' },
    signal,
  });
  return data.data;
}

/** Fenêtres « Contacter » et « Nouveau message » : renvoie la conversation du message. */
export async function contactMember(slug: string, body: string): Promise<number> {
  await ensureCsrf();
  const { data } = await http.post<{ data: { conversation_id: number } }>(
    `/members/${encodeURIComponent(slug)}/contact`,
    { body }
  );
  return data.data.conversation_id;
}
