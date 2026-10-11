// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useInbox } from '@/stores/inbox';
import { mountApp } from '@/testing/mount-app';
import * as notificationService from '@modules/notifications/services/notifications';
import * as messages from '../services/messages';
import MessagesView from './MessagesView.vue';

vi.mock('../services/messages', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/messages')>()),
  fetchConversations: vi.fn(),
  fetchConversation: vi.fn(),
  fetchMessages: vi.fn(),
  markConversationRead: vi.fn(),
  sendMessage: vi.fn(),
  searchRecipients: vi.fn(),
}));
vi.mock('@modules/notifications/services/notifications', () => ({
  fetchUnreadCounts: vi.fn().mockResolvedValue({ notifications: 0, messages: 0 }),
}));

const conversation = (
  id: number,
  name: string,
  unread: number,
  isMine = false
): messages.Conversation => ({
  id,
  contact: {
    id: id + 10,
    slug: name.toLowerCase().replace(' ', '-'),
    name,
    photo_url: null,
    place: 'Abidjan, Côte d’Ivoire',
    has_profile: true,
  },
  last_message: { excerpt: `Message de ${name}`, sent_at: '2026-10-10T08:57:00Z', is_mine: isMine },
  unread_count: unread,
});

function page(
  data: messages.Conversation[],
  meta: Partial<messages.ConversationPage['meta']> = {}
): messages.ConversationPage {
  return {
    data,
    meta: {
      current_page: 1,
      last_page: 1,
      total_conversations: data.length,
      unread_conversations: data.filter((item) => item.unread_count > 0).length,
      ...meta,
    },
  };
}

async function openPage(path = '/messages') {
  // q-page exige un q-layout : remplacé par un simple conteneur
  const result = await mountApp(MessagesView, {
    path,
    global: { stubs: { QPage: { template: '<div><slot /></div>' } } },
  });
  await flushPromises();
  return result;
}

describe('MessagesView', () => {
  beforeEach(() => {
    vi.mocked(messages.fetchConversations).mockReset();
    vi.mocked(messages.fetchConversations).mockResolvedValue(
      page([conversation(3, 'Fatou Ndiaye', 2), conversation(4, 'Leïla Bouzid', 0, true)])
    );
    vi.mocked(messages.fetchMessages).mockResolvedValue({
      messages: [{ id: 1, body: 'Bonjour', sent_at: '2026-10-10T08:57:00Z', is_mine: false }],
      hasMore: false,
    });
    vi.mocked(messages.markConversationRead).mockResolvedValue();
    vi.mocked(messages.searchRecipients).mockResolvedValue([]);
  });

  it('lists the conversations and invites to choose one', async () => {
    const { wrapper } = await openPage();

    const rows = wrapper.findAll('.conversation-row');
    expect(rows).toHaveLength(2);
    expect(rows[0]!.text()).toContain('Abidjan, Côte d’Ivoire');
    expect(rows[0]!.find('.conversation-row__count').text()).toBe('2');
    expect(rows[0]!.find('.sr-only').text()).toBe(', 2 messages non lus');
    expect(rows[1]!.find('.conversation-row__excerpt').text()).toBe(
      'Vous : Message de Leïla Bouzid'
    );
    expect(wrapper.find('#conversation-list-title').text()).toBe('Conversations2');
    expect(wrapper.text()).toContain('Les échanges font vivre le réseau');
    wrapper.unmount();
  });

  it('filters the list with the search of the header and the unread filter', async () => {
    const { wrapper } = await openPage('/messages?q=abidjan');

    expect(messages.fetchConversations).toHaveBeenLastCalledWith(
      1,
      { q: 'abidjan', unread: false },
      expect.anything()
    );

    const unread = wrapper.findAll('.conversation-list__filter .q-btn')[1]!;
    await unread.trigger('click');
    await flushPromises();
    expect(messages.fetchConversations).toHaveBeenLastCalledWith(
      1,
      { q: 'abidjan', unread: true },
      expect.anything()
    );
    wrapper.unmount();
  });

  it('opens the conversation of the address and marks it as read', async () => {
    const { wrapper } = await openPage('/messages/3');

    expect(wrapper.find('.conversation-header h2').text()).toBe('Fatou Ndiaye');
    expect(wrapper.find('.conversation-header').text()).toContain('Voir son profil');
    expect(wrapper.find('.conversation-row--active').text()).toContain('Fatou Ndiaye');
    expect(messages.fetchMessages).toHaveBeenCalledWith(3);
    expect(messages.markConversationRead).toHaveBeenCalledWith(3);
    expect(wrapper.find('.conversation-row--active .conversation-row__count').exists()).toBe(false);
    wrapper.unmount();
  });

  it('loads a conversation that is not in the filtered list', async () => {
    vi.mocked(messages.fetchConversation).mockResolvedValue(conversation(9, 'Mariam Keita', 0));
    const { wrapper } = await openPage('/messages/9?q=abidjan');

    expect(messages.fetchConversation).toHaveBeenCalledWith(9);
    expect(wrapper.find('.conversation-header h2').text()).toBe('Mariam Keita');
    wrapper.unmount();
  });

  it('offers to clear a search without result', async () => {
    vi.mocked(messages.fetchConversations).mockResolvedValue(page([], { total_conversations: 2 }));
    const { wrapper, router } = await openPage('/messages?q=zzz');

    expect(wrapper.text()).toContain('Aucune conversation ne correspond à « zzz ».');
    const clear = wrapper.findAll('button').find((item) => item.text() === 'Effacer la recherche')!;
    await clear.trigger('click');
    await flushPromises();
    expect(router.currentRoute.value.query.q).toBeUndefined();
    wrapper.unmount();
  });

  it('invites to write a first message when there is no conversation', async () => {
    vi.mocked(messages.fetchConversations).mockResolvedValue(page([]));
    const { wrapper } = await openPage();

    expect(wrapper.text()).toContain('Aucune conversation pour l’instant.');
    expect(wrapper.find('.inbox__state h2').text()).toBe('Écrire à une médiatrice');
    wrapper.unmount();
  });

  it('offers to retry when the list cannot be loaded', async () => {
    vi.mocked(messages.fetchConversations).mockRejectedValueOnce(new Error('offline'));
    const { wrapper } = await openPage();

    expect(wrapper.text()).toContain('Impossible d’afficher vos conversations.');
    const retry = wrapper.findAll('button').find((item) => item.text() === 'Réessayer')!;
    await retry.trigger('click');
    await flushPromises();
    expect(wrapper.findAll('.conversation-row')).toHaveLength(2);
    wrapper.unmount();
  });

  it('reloads the list and adds the new messages of the open conversation when some arrive', async () => {
    const { wrapper } = await openPage('/messages/3');
    vi.mocked(messages.markConversationRead).mockClear();

    // Nouveau message de Fatou : la conversation a de nouveau un non-lu
    vi.mocked(messages.fetchConversations).mockResolvedValue(
      page([
        {
          ...conversation(3, 'Fatou Ndiaye', 1),
          last_message: { excerpt: 'Treize', sent_at: '2026-10-10T09:30:00Z', is_mine: false },
        },
        conversation(4, 'Leïla Bouzid', 0, true),
      ])
    );
    vi.mocked(messages.fetchMessages).mockResolvedValue({
      messages: [
        { id: 2, body: 'Treize', sent_at: '2026-10-10T09:30:00Z', is_mine: false },
        { id: 1, body: 'Bonjour', sent_at: '2026-10-10T08:57:00Z', is_mine: false },
      ],
      hasMore: false,
    });
    useInbox().unreadMessages = 1;
    await flushPromises();

    expect(wrapper.find('.conversation-row--active .conversation-row__excerpt').text()).toBe(
      'Treize'
    );
    expect(wrapper.findAll('.message__body').map((body) => body.text())).toEqual([
      'Bonjour',
      'Treize',
    ]);
    expect(messages.markConversationRead).toHaveBeenCalledWith(3);
    expect(wrapper.find('.conversation-row--active .conversation-row__count').exists()).toBe(false);
    wrapper.unmount();
  });

  it('checks the unread counters about every 20 seconds while the page is shown', async () => {
    vi.useFakeTimers({ toFake: ['setInterval', 'clearInterval'] });
    const { wrapper } = await openPage();
    vi.mocked(notificationService.fetchUnreadCounts).mockClear();

    vi.advanceTimersByTime(21_000);
    await flushPromises();
    expect(notificationService.fetchUnreadCounts).toHaveBeenCalledTimes(1);

    wrapper.unmount();
    vi.advanceTimersByTime(21_000);
    expect(notificationService.fetchUnreadCounts).toHaveBeenCalledTimes(1);
    vi.useRealTimers();
  });
});
