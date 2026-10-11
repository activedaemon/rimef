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
  body: string;
  sent_at: string;
  is_mine: boolean;
}

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
