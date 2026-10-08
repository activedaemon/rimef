// @vitest-environment happy-dom
import { flushPromises } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import HeaderSearch from './HeaderSearch.vue';

describe('HeaderSearch', () => {
  it('goes to the search page with the typed terms', async () => {
    const { wrapper, router } = await mountApp(HeaderSearch);

    const form = wrapper.find('form[role="search"]');
    await form.find('input').setValue('  médiation Dakar  ');
    await form.trigger('submit');
    await flushPromises();

    expect(router.currentRoute.value.name).toBe('search');
    expect(router.currentRoute.value.query.q).toBe('médiation Dakar');
    wrapper.unmount();
  });

  it('does not search for blank terms', async () => {
    const { wrapper, router } = await mountApp(HeaderSearch);

    const form = wrapper.find('form[role="search"]');
    await form.find('input').setValue('   ');
    await form.trigger('submit');
    await flushPromises();

    expect(router.currentRoute.value.name).toBe('home');
    wrapper.unmount();
  });

  it('uses the placeholder of the current section', async () => {
    const { wrapper } = await mountApp(HeaderSearch, { path: '/reseau' });

    expect(wrapper.find('form[role="search"] input').attributes('placeholder')).toBe(
      'Rechercher une médiatrice…'
    );
    wrapper.unmount();
  });

  it('passes the current section to the search page', async () => {
    const { wrapper, router } = await mountApp(HeaderSearch, { path: '/agenda' });

    const form = wrapper.find('form[role="search"]');
    await form.find('input').setValue('Dakar');
    await form.trigger('submit');
    await flushPromises();

    expect(router.currentRoute.value.query).toEqual({ q: 'Dakar', rubrique: 'agenda' });
    wrapper.unmount();
  });

  it('keeps the section when searching again from the results page', async () => {
    const { wrapper, router } = await mountApp(HeaderSearch, {
      path: '/recherche?q=paix&rubrique=ressources',
    });

    const form = wrapper.find('form[role="search"]');
    await form.find('input').setValue('rapport');
    await form.trigger('submit');
    await flushPromises();

    expect(router.currentRoute.value.query).toEqual({ q: 'rapport', rubrique: 'ressources' });
    wrapper.unmount();
  });

  it('shows the current terms on the search page', async () => {
    const { wrapper } = await mountApp(HeaderSearch, { path: '/recherche?q=paix' });

    expect(wrapper.find('form[role="search"] input').element).toHaveProperty('value', 'paix');
    wrapper.unmount();
  });
});
