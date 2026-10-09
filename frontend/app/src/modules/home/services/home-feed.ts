// Contenu de l'accueil (GET /api/home) : événement à la une, prochains rendez-vous, nouvelles
// médiatrices. Les dates arrivent en UTC et sont mises en forme ici, pour les cartes.
import { dayAndMonth, daysAgo, formatDateRange } from '@/lib/dates';
import { ensureCsrf, http } from '@/lib/http';

/** Médiatrice affichée en avatar, avec un lien vers sa fiche. */
export interface Person {
  id: number;
  name: string;
  photo: string | null;
}

export interface FeaturedEvent {
  id: number;
  title: string;
  dates: string;
  place: string | null;
  description: string | null;
  /** Quelques participantes (photos d'abord) ; attendeeCount donne le total. */
  attendees: Person[];
  attendeeCount: number;
  isParticipating: boolean;
}

export interface Meeting {
  id: number;
  day: string;
  month: string;
  title: string;
  place: string | null;
  attendeeCount: number;
}

export interface NetworkNews {
  person: Person;
  /** Suite de la phrase après le nom. */
  action: string;
  when: string;
}

export interface HomeFeed {
  featuredEvent: FeaturedEvent | null;
  meetings: Meeting[];
  news: NetworkNews[];
}

interface ApiPerson {
  id: number;
  name: string;
  photo_url: string | null;
}

interface ApiEvent {
  id: number;
  title: string;
  starts_at: string;
  ends_at: string | null;
  place: string | null;
  attendee_count: number;
}

interface ApiHome {
  featured_event:
    | (ApiEvent & { description: string | null; is_participating: boolean; attendees: ApiPerson[] })
    | null;
  meetings: ApiEvent[];
  news: (ApiPerson & { joined_at: string })[];
}

const toPerson = (person: ApiPerson): Person => ({
  id: person.id,
  name: person.name,
  photo: person.photo_url,
});

export async function fetchHomeFeed(): Promise<HomeFeed> {
  const { data } = await http.get<{ data: ApiHome }>('/home');
  const { featured_event: featured, meetings, news } = data.data;

  return {
    featuredEvent: featured && {
      id: featured.id,
      title: featured.title,
      dates: formatDateRange(featured.starts_at, featured.ends_at),
      place: featured.place,
      description: featured.description,
      attendees: featured.attendees.map(toPerson),
      attendeeCount: featured.attendee_count,
      isParticipating: featured.is_participating,
    },
    meetings: meetings.map((meeting) => ({
      id: meeting.id,
      ...dayAndMonth(meeting.starts_at),
      title: meeting.title,
      place: meeting.place,
      attendeeCount: meeting.attendee_count,
    })),
    news: news.map((item) => ({
      person: toPerson(item),
      action: 'a rejoint le réseau',
      when: daysAgo(item.joined_at),
    })),
  };
}

/** « J'y participe » : inscrit ou désinscrit la personne connectée. */
export async function setParticipation(eventId: number, participating: boolean): Promise<void> {
  await ensureCsrf();
  if (participating) {
    await http.put(`/events/${eventId}/participation`);
  } else {
    await http.delete(`/events/${eventId}/participation`);
  }
}
