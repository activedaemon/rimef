// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import type { ResourceMediator } from '../services/resources';
import ResourceMediatorCard from './ResourceMediatorCard.vue';

const AMINATA: ResourceMediator = {
  id: 7,
  slug: 'aminata-diallo',
  name: 'Aminata Diallo',
  first_name: 'Aminata',
  photo_url: null,
  country: 'Sénégal',
  expertise: 'Médiation communautaire',
  role: { value: 'contributor', label: 'Contributrice' },
  note: 'Son expérience est citée au chapitre 3.',
  quote: null,
};

describe('ResourceMediatorCard', () => {
  it('shows the mediator, her role and the note of the link, with her profile', async () => {
    const { wrapper } = await mountApp(ResourceMediatorCard, { props: { mediator: AMINATA } });

    expect(wrapper.find('h3 a').attributes('href')).toBe('/reseau/aminata-diallo');
    expect(wrapper.find('.mediator-card__where').text()).toBe('Sénégal · Médiation communautaire');
    expect(wrapper.find('.q-badge').text()).toBe('Contributrice');
    expect(wrapper.find('.mediator-card__badge--strong').exists()).toBe(true);
    expect(wrapper.find('.mediator-card__note').text()).toBe(
      'Son expérience est citée au chapitre 3.'
    );
    expect(wrapper.find('blockquote').exists()).toBe(false);
    wrapper.unmount();
  });

  it('shows the recommendation of a mediator who only recommends it, and asks to write to her', async () => {
    const { wrapper } = await mountApp(ResourceMediatorCard, {
      props: { mediator: { ...AMINATA, role: null, note: null, quote: 'Un rapport précieux.' } },
    });

    expect(wrapper.find('.q-badge').text()).toBe('A recommandé cette ressource');
    expect(wrapper.find('.mediator-card__badge').exists()).toBe(true);
    expect(wrapper.find('blockquote').text()).toBe('« Un rapport précieux. »');

    await wrapper.find('.mediator-card__write').trigger('click');
    expect(wrapper.emitted('write')?.[0]).toEqual([
      { ...AMINATA, role: null, note: null, quote: 'Un rapport précieux.' },
    ]);
    wrapper.unmount();
  });
});
