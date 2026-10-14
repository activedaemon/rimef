// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { ResourceVoice } from '../services/resources';
import ResourceVoices from './ResourceVoices.vue';

const FATOU: ResourceVoice = {
  id: 1,
  quote: 'Un rapport précieux pour défendre la place des femmes à la table des négociations.',
  resource: {
    id: 3,
    slug: 'women-mediators',
    title: 'Women Mediators and Inclusive Peace Processes',
    url: 'https://www.c-r.org/',
  },
  member: {
    id: 9,
    slug: 'fatou-ndiaye',
    name: 'Fatou Ndiaye',
    photo_url: null,
    country: 'Côte d’Ivoire',
    expertise: 'Prévention des conflits',
  },
};

describe('ResourceVoices', () => {
  it('shows each review with its resource and the mediator, linked to her profile', async () => {
    const { wrapper } = await mountApp(ResourceVoices, { props: { voices: [FATOU] } });

    expect(wrapper.find('h2').text()).toBe('Lues et partagées par les médiatrices');
    expect(wrapper.find('blockquote').text()).toBe(
      '« Un rapport précieux pour défendre la place des femmes à la table des négociations. »'
    );
    const resource = wrapper.find('.resource-voice__on a');
    expect(resource.text()).toBe('Women Mediators and Inclusive Peace Processes');
    expect(resource.attributes('href')).toBe('/ressources/women-mediators');
    const member = wrapper.find('.resource-voice__name');
    expect(member.text()).toBe('Fatou Ndiaye');
    expect(member.attributes('href')).toBe('/reseau/fatou-ndiaye');
    expect(wrapper.find('.resource-voice__who small').text()).toBe(
      'Côte d’Ivoire · Prévention des conflits'
    );
    wrapper.unmount();
  });

  it('skips the detail line of a mediator without country or expertise', async () => {
    const { wrapper } = await mountApp(ResourceVoices, {
      props: {
        voices: [{ ...FATOU, member: { ...FATOU.member, country: null, expertise: null } }],
      },
    });

    expect(wrapper.find('.resource-voice__who small').exists()).toBe(false);
    wrapper.unmount();
  });

  it('announces that sharing a resource is coming soon', async () => {
    const { wrapper } = await mountApp(ResourceVoices, { props: { voices: [FATOU] } });

    const share = wrapper
      .findAll('button')
      .find((button) => button.text() === 'Partager une ressource');
    expect(share).toBeDefined();
    await share!.trigger('click');
    await flushPromises();

    expect(document.body.textContent).toContain('Le partage de ressources arrive bientôt.');
    wrapper.unmount();
  });
});
