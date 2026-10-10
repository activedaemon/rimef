// Messagerie interne : conversations par paire de membres (GET/POST /api/conversations…)
// et fenêtre « Contacter » des fiches (POST /api/members/{slug}/contact).
import { ensureCsrf, http } from '@/lib/http';

/** Longueur maximale d'un message (SendMessageRequest::MAX_LENGTH). */
export const MAX_MESSAGE_LENGTH = 2000;

export type ContactSubject = 'expertise' | 'event_invitation' | 'co_mediation' | 'peer_exchange';

/** Objets proposés dans la fenêtre « Contacter » (maquette). */
export const CONTACT_SUBJECTS: { value: ContactSubject; label: string }[] = [
  { value: 'expertise', label: 'Demande d’expertise' },
  { value: 'event_invitation', label: 'Invitation à un événement' },
  { value: 'co_mediation', label: 'Proposition de co-médiation' },
  { value: 'peer_exchange', label: 'Échange entre pairs' },
];

export interface ConversationContact {
  id: number;
  slug: string;
  name: string;
  photo_url: string | null;
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
  /** Objet de la prise de contact (premier message envoyé depuis une fiche). */
  subject: string | null;
  sent_at: string;
  is_mine: boolean;
}

export interface ConversationPage {
  data: Conversation[];
  meta: { current_page: number; last_page: number };
}

export async function fetchConversations(page = 1): Promise<ConversationPage> {
  const { data } = await http.get<ConversationPage>('/conversations', { params: { page } });
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

/** Fenêtre « Contacter » : renvoie la conversation où le message a été écrit. */
export async function contactMember(
  slug: string,
  subject: ContactSubject,
  body: string
): Promise<number> {
  await ensureCsrf();
  const { data } = await http.post<{ data: { conversation_id: number } }>(
    `/members/${encodeURIComponent(slug)}/contact`,
    { subject, body }
  );
  return data.data.conversation_id;
}
