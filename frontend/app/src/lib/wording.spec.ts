import { describe, expect, it } from 'vitest';

import { ofName } from './wording';

describe('wording', () => {
  it('elides « de » before a vowel', () => {
    expect(ofName('Olivia')).toBe('d’Olivia');
    expect(ofName('Élodie')).toBe('d’Élodie');
    expect(ofName('Marcel')).toBe('de Marcel');
    expect(ofName('Hélène')).toBe('de Hélène');
  });
});
