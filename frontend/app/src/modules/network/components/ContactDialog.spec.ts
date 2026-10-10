// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import * as messages from '@modules/messages/services/messages';
import { mountApp } from '@/testing/mount-app';
import type { MemberProfile } from '../services/members';
import ContactDialog from './ContactDialog.vue';

vi.mock('@modules/messages/services/messages', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@modules/messages/services/messages')>()),
  contactMember: vi.fn(),
}));

const AMINATA = {
  id: 7,
  slug: 'aminata-diallo',
  name: 'Aminata Diallo',
  first_name: 'Aminata',
  photo_url: null,
} as MemberProfile;

async function openDialog() {
  const result = await mountApp(ContactDialog, { props: { profile: AMINATA, modelValue: true } });
  await flushPromises();
  return result;
}

const sendButton = () =>
  Array.from(document.body.querySelectorAll<HTMLButtonElement>('button')).find((button) =>
    button.textContent?.includes('Envoyer le message')
  )!;

describe('ContactDialog', () => {
  beforeEach(() => {
    vi.mocked(messages.contactMember).mockReset();
    document.body.innerHTML = '';
  });

  it('shows the mockup texts, with an empty message', async () => {
    const { wrapper } = await openDialog();

    expect(document.body.textContent).toContain('Contacter Aminata');
    expect(document.body.textContent).toContain('Message transmis via la messagerie du réseau');
    expect(document.body.textContent).not.toContain('prévenue par email');
    expect(document.body.querySelector('textarea')!.value).toBe('');
    expect(sendButton().disabled).toBe(true);
    wrapper.unmount();
  });

  it('sends the subject and the message, then closes', async () => {
    vi.mocked(messages.contactMember).mockResolvedValue(12);
    const { wrapper } = await openDialog();

    const textarea = document.body.querySelector('textarea')!;
    textarea.value = '  Bonjour Aminata !  ';
    textarea.dispatchEvent(new Event('input'));
    await flushPromises();
    sendButton().click();
    await flushPromises();

    expect(messages.contactMember).toHaveBeenCalledWith(
      'aminata-diallo',
      'expertise',
      'Bonjour Aminata !'
    );
    expect(wrapper.emitted('update:modelValue')).toEqual([[false]]);
    wrapper.unmount();
  });

  it('stays open when the message cannot be sent', async () => {
    vi.mocked(messages.contactMember).mockRejectedValue(new Error('offline'));
    const { wrapper } = await openDialog();

    const textarea = document.body.querySelector('textarea')!;
    textarea.value = 'Bonjour';
    textarea.dispatchEvent(new Event('input'));
    await flushPromises();
    sendButton().click();
    await flushPromises();

    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    wrapper.unmount();
  });
});
