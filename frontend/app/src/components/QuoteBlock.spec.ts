// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import QuoteBlock from './QuoteBlock.vue';

describe('QuoteBlock', () => {
  it('shows the quote between French quotation marks, signed RIMeF, with the leaves', async () => {
    const { wrapper } = await mountApp(QuoteBlock, {
      props: { text: 'Un réseau pensé par et pour les médiatrices francophones.' },
    });

    expect(wrapper.find('blockquote').text()).toBe(
      '« Un réseau pensé par et pour les médiatrices francophones. »'
    );
    expect(wrapper.find('figcaption').text()).toBe('— RIMeF');
    expect(wrapper.find('svg[aria-hidden="true"]').exists()).toBe(true);
    wrapper.unmount();
  });

  it('renders the compact ivory card used on the home page', async () => {
    const { wrapper } = await mountApp(QuoteBlock, {
      props: { text: 'Des processus de paix inclusifs.', variant: 'card', tone: 'ivory' },
    });

    expect(wrapper.classes()).toEqual(
      expect.arrayContaining(['quote-block--card', 'quote-block--ivory'])
    );
    wrapper.unmount();
  });

  it('accepts another signature', async () => {
    const { wrapper } = await mountApp(QuoteBlock, {
      props: { text: 'Solidarité mutuelle.', cite: 'La vision du RIMeF' },
    });

    expect(wrapper.find('figcaption').text()).toBe('— La vision du RIMeF');
    wrapper.unmount();
  });
});
