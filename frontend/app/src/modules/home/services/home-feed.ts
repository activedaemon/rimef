// Contenu de l'accueil : événement à la une, prochains rendez-vous, nouveautés, ressources.

export interface FeaturedEvent {
  id: string;
  title: string;
  dates: string;
  place: string;
  description: string;
  /** Quelques participantes affichées en avatars ; attendeeCount donne le total. */
  attendees: string[];
  attendeeCount: number;
}

export interface Meeting {
  id: string;
  day: string;
  month: string;
  title: string;
  place: string;
  attendeeCount: number;
}

export interface NetworkNews {
  id: string;
  personName: string;
  /** Suite de la phrase après le nom : « a rejoint le réseau ». */
  action: string;
  when: string;
}

export interface ResourceSummary {
  id: string;
  title: string;
  type: string;
  detail: string;
  icon: string;
}

export interface HomeFeed {
  featuredEvent: FeaturedEvent | null;
  meetings: Meeting[];
  news: NetworkNews[];
  resources: ResourceSummary[];
}

// DÉMONSTRATION — contenus repris de la maquette, à remplacer par l'API quand l'agenda,
// les membres et les ressources existeront. Les noms sont ceux des médiatrices fondatrices
// (présentation locale uniquement) : activités et participations sont fictives.
// À retirer avant toute mise en ligne ou diffusion de captures.
const DEMO_FEED: HomeFeed = {
  featuredEvent: {
    id: 'demo-paris-peace-forum',
    title: 'Paris Peace Forum',
    dates: '12–13 novembre 2026',
    place: 'Paris, France',
    description: 'Un espace de dialogue pour des solutions multilatérales aux défis globaux.',
    attendees: ['Delphine Borione', 'Kalinda Magloire', 'Fatima Maïga', 'Esther Omam'],
    attendeeCount: 8,
  },
  meetings: [
    {
      id: 'demo-eu-cop',
      day: '18',
      month: 'OCT',
      title: 'EU Community of Practice',
      place: 'Bruxelles, Belgique',
      attendeeCount: 5,
    },
    {
      id: 'demo-dakar',
      day: '26',
      month: 'OCT',
      title: 'Médiation et processus de paix',
      place: 'Dakar, Sénégal',
      attendeeCount: 3,
    },
  ],
  news: [
    {
      id: 'demo-n1',
      personName: 'Marie-Joëlle Zahar',
      action: 'a rejoint le réseau comme médiatrice fondatrice',
      when: 'Il y a 2 jours',
    },
    {
      id: 'demo-n2',
      personName: 'Achta Djibrine Sy',
      action: 'a participé à l’atelier de lancement à Paris',
      when: 'Il y a 3 jours',
    },
    {
      id: 'demo-n3',
      personName: 'Nelly Godelive Mbangu',
      action: 'est intervenue lors du lancement à Sciences Po',
      when: 'Il y a 4 jours',
    },
  ],
  resources: [
    {
      id: 'demo-r1',
      title: 'Femmes médiatrices : des actrices clés pour des paix durables',
      type: 'Rapport',
      detail: 'Avril 2026',
      icon: 'file-text',
    },
    {
      id: 'demo-r2',
      title: 'Paroles de médiatrices',
      type: 'Podcast',
      detail: '34 min',
      icon: 'microphone',
    },
  ],
};

export async function fetchHomeFeed(): Promise<HomeFeed> {
  return DEMO_FEED;
}
