// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import { useSession } from '@/stores/session';
import type { Member } from '@modules/network/services/members';
import * as messages from '../services/messages';
import NewMessageDialog from './NewMessageDialog.vue';

vi.mock('../services/messages', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/messages')>()),
  searchRecipients: vi.fn(),
  contactMember: vi.fn(),
}));

const member = (id: number, name: string, conversationId: number | null = null): Member => ({
  id,
  slug: name.toLowerCase().replace(' ', '-'),
  name,
  country: { code: 'CI', name: 'Côte d’Ivoire' },
  region: null,
  organization: null,
  is_available: false,
  is_favorite: false,
  conversation_id: conversationId,
  next_event: null,
  photo_url: null,
  expertises: ['Prévention des conflits'],
});

async function openDialog() {
  const result = await mountApp(NewMessageDialog, { props: { modelValue: false } });
  const session = useSession();
  session.user = { id: 1 } as typeof session.user;
  await result.wrapper.setProps({ modelValue: true });
  await flushPromises();
  return result;
}

const options = () => Array.from(document.body.querySelectorAll('.new-message__option'));
const button = (label: string) =>
  Array.from(document.body.querySelectorAll<HTMLButtonElement>('button')).find((element) =>
    element.textContent?.includes(label)
  )!;

describe('NewMessageDialog', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.mocked(messages.searchRecipients).mockResolvedValue([
      member(1, 'David Gautier'),
      member(3, 'Fatou Ndiaye', 12),
      member(4, 'Leïla Bouzid'),
    ]);
  });

  it('lists the mediators except oneself, with the ongoing conversations', async () => {
    const { wrapper } = await openDialog();

    expect(messages.searchRecipients).toHaveBeenCalledWith('', expect.anything());
    expect(
      options().map((option) => option.querySelector('.new-message__name')!.textContent)
    ).toEqual(['Fatou Ndiaye', 'Leïla Bouzid']);
    expect(options()[0]!.textContent).toContain('Conversation en cours');
    expect(options()[0]!.textContent).toContain('Côte d’Ivoire · Prévention des conflits');
    wrapper.unmount();
  });

  it('opens the ongoing conversation instead of writing a new message', async () => {
    const { wrapper } = await openDialog();

    (options()[0] as HTMLElement).click();
    await flushPromises();

    expect(wrapper.emitted('open')).toEqual([[12]]);
    expect(wrapper.emitted('update:modelValue')).toEqual([[false]]);
    wrapper.unmount();
  });

  it('writes to a new recipient, who can be changed', async () => {
    vi.mocked(messages.contactMember).mockResolvedValue(20);
    const { wrapper } = await openDialog();

    (options()[1] as HTMLElement).click();
    await flushPromises();
    expect(document.body.textContent).toContain('Écrire à Leïla');

    button('Changer de destinataire').click();
    await flushPromises();
    expect(document.body.textContent).toContain('Destinataire');

    (options()[1] as HTMLElement).click();
    await flushPromises();
    const field = document.getElementById('contact-body') as HTMLTextAreaElement;
    field.value = 'Bonjour Leïla';
    field.dispatchEvent(new Event('input'));
    await flushPromises();
    button('Envoyer le message').click();
    await flushPromises();

    expect(messages.contactMember).toHaveBeenCalledWith('leïla-bouzid', 'Bonjour Leïla');
    expect(wrapper.emitted('open')).toEqual([[20]]);
    wrapper.unmount();
  });
});
