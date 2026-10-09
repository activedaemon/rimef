// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { mountApp } from '@/testing/mount-app';
import UpcomingMeetingsCard from './UpcomingMeetingsCard.vue';

describe('UpcomingMeetingsCard', () => {
  it('lists the meetings with their date, place and attendees, and links to the agenda', async () => {
    const { wrapper } = await mountApp(UpcomingMeetingsCard, {
      props: {
        meetings: [
          {
            id: 'm1',
            day: '18',
            month: 'OCT',
            title: 'EU Community',
            place: 'Bruxelles',
            attendeeCount: 5,
          },
          { id: 'm2', day: '26', month: 'OCT', title: 'Dakar', place: 'Dakar', attendeeCount: 1 },
        ],
      },
    });

    const items = wrapper.findAll('li');
    expect(items[0]!.text()).toContain('18');
    expect(items[0]!.text()).toContain('OCT');
    expect(items[0]!.text()).toContain('5 médiatrices');
    expect(items[1]!.text()).toContain('1 médiatrice');
    expect(items[1]!.text()).not.toContain('1 médiatrices');
    expect(wrapper.find('.link-more').attributes('href')).toBe('/agenda');
    wrapper.unmount();
  });
});
