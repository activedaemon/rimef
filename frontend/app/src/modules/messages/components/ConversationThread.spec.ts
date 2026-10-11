// @vitest-environment happy-dom
import { AxiosError, AxiosHeaders } from 'axios';
import { flushPromises } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import * as messages from '../services/messages';
import ConversationThread from './ConversationThread.vue';

vi.mock('../services/messages', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/messages')>()),
  fetchMessages: vi.fn(),
  markConversationRead: vi.fn(),
  sendMessage: vi.fn(),
}));
vi.mock('@modules/notifications/services/notifications', () => ({
  fetchUnreadCounts: vi.fn().mockResolvedValue({ notifications: 0, messages: 0 }),
}));

const message = (id: number, body: string, isMine: boolean, sentAt: string): messages.Message => ({
  id,
  body,
  sent_at: sentAt,
  is_mine: isMine,
});

async function mountThread(props: { canReply?: boolean; conversationId?: number } = {}) {
  const result = await mountApp(ConversationThread, {
    props: { conversationId: 3, contactName: 'Fatou Ndiaye', ...props },
  });
  await flushPromises();
  return result;
}

async function write(wrapper: Awaited<ReturnType<typeof mountThread>>['wrapper'], text: string) {
  await wrapper.find('textarea').setValue(text);
  await wrapper.find('form').trigger('submit');
}

describe('ConversationThread', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] });
    vi.setSystemTime(new Date('2026-10-10T11:20:00'));
    // L'API donne les plus récents d'abord
    vi.mocked(messages.fetchMessages).mockResolvedValue({
      messages: [
        message(2, 'Avec plaisir', false, '2026-10-10T09:15:00'),
        message(1, 'Bonjour', true, '2026-10-05T16:40:00'),
      ],
      hasMore: false,
    });
    vi.mocked(messages.markConversationRead).mockResolvedValue();
    vi.mocked(messages.sendMessage).mockReset();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('groups the messages by day, oldest first, and marks the conversation as read', async () => {
    const { wrapper } = await mountThread();

    expect(wrapper.findAll('.thread__day-label').map((label) => label.text())).toEqual([
      'Lundi 5 octobre',
      'Aujourd’hui',
    ]);
    const bubbles = wrapper.findAll('.message');
    expect(bubbles.map((bubble) => bubble.find('.message__body').text())).toEqual([
      'Bonjour',
      'Avec plaisir',
    ]);
    expect(bubbles[0]!.classes()).toContain('message--mine');
    expect(bubbles[1]!.find('.sr-only').text()).toBe('Fatou Ndiaye :');
    expect(bubbles[1]!.find('time').text()).toBe('09:15');
    expect(messages.markConversationRead).toHaveBeenCalledWith(3);
    expect(wrapper.emitted('read')).toHaveLength(1);
    wrapper.unmount();
  });

  it('shows a reply at once, then replaces it with the saved message', async () => {
    let resolve: (value: messages.Message) => void = () => undefined;
    vi.mocked(messages.sendMessage).mockReturnValue(new Promise((done) => (resolve = done)));
    const { wrapper } = await mountThread();

    await write(wrapper, '  À bientôt  ');
    await flushPromises();
    const last = () => wrapper.findAll('.message').at(-1)!;
    expect(last().find('.message__body').text()).toBe('À bientôt');
    expect(last().text()).toContain('Envoi en cours…');
    expect(wrapper.find('textarea').element.value).toBe('');

    resolve(message(4, 'À bientôt', true, '2026-10-10T11:20:00'));
    await flushPromises();
    expect(last().text()).not.toContain('Envoi en cours…');
    expect(messages.sendMessage).toHaveBeenCalledWith(3, 'À bientôt');
    expect(wrapper.emitted('sent')).toEqual([
      [message(4, 'À bientôt', true, '2026-10-10T11:20:00')],
    ]);
    wrapper.unmount();
  });

  it('keeps a failed reply with a retry button', async () => {
    vi.mocked(messages.sendMessage)
      .mockRejectedValueOnce(new Error('offline'))
      .mockResolvedValueOnce(message(5, 'Merci', true, '2026-10-10T11:21:00'));
    const { wrapper } = await mountThread();

    await write(wrapper, 'Merci');
    await flushPromises();
    expect(wrapper.find('.message--failed').text()).toContain('Échec de l’envoi');

    await wrapper.find('.message__retry').trigger('click');
    await flushPromises();
    expect(messages.sendMessage).toHaveBeenCalledTimes(2);
    expect(wrapper.find('.message--failed').exists()).toBe(false);
    wrapper.unmount();
  });

  it('keeps the draft of each conversation during the visit', async () => {
    const { wrapper } = await mountThread({ conversationId: 8 });
    await wrapper.find('textarea').setValue('Brouillon');
    wrapper.unmount();

    const { wrapper: again } = await mountThread({ conversationId: 8 });
    expect(again.find('textarea').element.value).toBe('Brouillon');
    again.unmount();
  });

  it('hides the reply when the contact account was deleted', async () => {
    const { wrapper } = await mountThread({ canReply: false });

    expect(wrapper.find('form').exists()).toBe(false);
    wrapper.unmount();
  });

  it('reports an unknown conversation', async () => {
    vi.mocked(messages.fetchMessages).mockRejectedValue(
      new AxiosError('Not Found', '404', undefined, undefined, {
        status: 404,
        statusText: 'Not Found',
        data: {},
        headers: {},
        config: { headers: new AxiosHeaders() },
      })
    );
    const { wrapper } = await mountThread();

    expect(wrapper.emitted('not-found')).toHaveLength(1);
    wrapper.unmount();
  });
});
