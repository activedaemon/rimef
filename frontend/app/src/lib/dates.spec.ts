import { describe, expect, it } from 'vitest';

import { dayAndMonth, daysAgo, formatDateRange } from './dates';

describe('dates', () => {
  it('formats a single day and ranges within a month, across months and years', () => {
    expect(formatDateRange('2026-10-18T07:00:00Z')).toBe('18 octobre 2026');
    expect(formatDateRange('2026-11-12T08:00:00Z', '2026-11-13T17:00:00Z')).toBe(
      '12–13 novembre 2026'
    );
    expect(formatDateRange('2026-11-30T08:00:00Z', '2026-12-02T17:00:00Z')).toBe(
      '30 novembre – 2 décembre 2026'
    );
    expect(formatDateRange('2026-12-31T08:00:00Z', '2027-01-02T17:00:00Z')).toBe(
      '31 décembre 2026 – 2 janvier 2027'
    );
  });

  it('gives the day and the short month in capitals for the date tile', () => {
    expect(dayAndMonth('2026-10-18T07:00:00Z')).toEqual({ day: '18', month: 'OCT' });
    expect(dayAndMonth('2026-12-05T08:00:00Z')).toEqual({ day: '5', month: 'DÉC' });
  });

  it('says how many days ago, in calendar days', () => {
    const now = new Date('2026-10-10T10:00:00');
    expect(daysAgo('2026-10-10T08:00:00', now)).toBe('Aujourd’hui');
    expect(daysAgo('2026-10-09T23:00:00', now)).toBe('Hier');
    expect(daysAgo('2026-10-07T12:00:00', now)).toBe('Il y a 3 jours');
  });
});
