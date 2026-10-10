// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import * as messages from '../services/messages';
import ConversationView from './ConversationView.vue';

vi.mock('../services/messages', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/messages')>()),
  fetchConversation: vi.fn(),
  fetchMessages: vi.fn(),
  markConversationRead: vi.fn(),
  sendMessage: vi.fn(),
}));
vi.mock('@modules/notifications/services/notifications', () => ({
  fetchUnreadCounts: vi.fn().mockResolvedValue({ notifications: 0, messages: 0 }),
}));

const message = (id: number, body: string, isMine: boolean): messages.Message => ({
  id,
  body,
  subject: id === 1 ? 'Échange entre pairs' : null,
  sent_at: '2026-10-10T12:00:00Z',
  is_mine: isMine,
});

async function openConversation() {
  // q-page exige un q-layout : remplacé par un simple conteneur
  const result = await mountApp(ConversationView, {
    path: '/messages/3',
    global: { stubs: { QPage: { template: '<div><slot /></div>' } } },
  });
  await flushPromises();
  return result;
}

describe('ConversationView', () => {
  beforeEach(() => {
    vi.mocked(messages.fetchConversation).mockResolvedValue({
      id: 3,
      contact: {
        id: 7,
        slug: 'fatou-ndiaye',
        name: 'Fatou Ndiaye',
        photo_url: null,
        has_profile: true,
      },
      last_message: null,
      unread_count: 1,
    });
    // L'API donne les plus récents d'abord
    vi.mocked(messages.fetchMessages).mockResolvedValue({
      messages: [message(2, 'Avec plaisir', false), message(1, 'Bonjour Fatou', true)],
      hasMore: false,
    });
    vi.mocked(messages.markConversationRead).mockResolvedValue();
    window.scrollTo = vi.fn();
  });

  it('shows the messages oldest first and marks the conversation as read', async () => {
    const { wrapper } = await openConversation();

    expect(wrapper.find('h1').text()).toBe('Fatou Ndiaye');
    expect(wrapper.find('h1 a').attributes('href')).toBe('/reseau/fatou-ndiaye');
    const bubbles = wrapper.findAll('.bubble');
    expect(bubbles.map((bubble) => bubble.find('.bubble__body').text())).toEqual([
      'Bonjour Fatou',
      'Avec plaisir',
    ]);
    expect(bubbles[0]!.classes()).toContain('bubble--mine');
    expect(bubbles[0]!.find('.bubble__subject').text()).toBe('Échange entre pairs');
    expect(messages.markConversationRead).toHaveBeenCalledWith(3);
    wrapper.unmount();
  });

  it('sends a reply and adds it at the end', async () => {
    vi.mocked(messages.sendMessage).mockResolvedValue(message(4, 'À bientôt', true));
    const { wrapper } = await openConversation();

    await wrapper.find('textarea').setValue('À bientôt');
    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(messages.sendMessage).toHaveBeenCalledWith(3, 'À bientôt');
    expect(wrapper.findAll('.bubble__body').at(-1)!.text()).toBe('À bientôt');
    expect(wrapper.find('textarea').element.value).toBe('');
    wrapper.unmount();
  });
});
