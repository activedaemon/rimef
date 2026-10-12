<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventRole;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Événements repris de la maquette, avec des participantes de l'annuaire : en environnement
 * local uniquement (TenantDatabaseSeeder), après DemoMembersSeeder et ExpertiseSeeder.
 * Relançable : les événements sont retrouvés par leur titre.
 */
class DemoEventsSeeder extends Seeder
{
    /**
     * Début et fin à l'heure de Paris ; pays null = en ligne ; thématiques = noms d'expertises ;
     * participantes = adresses de DemoMembersSeeder, sans @example.org.
     *
     * @var list<array{title: string, type: EventType, start: string, end: string|null, city: string|null, country: string|null, featured?: bool, description?: string, themes: list<string>, participants: list<string>}>
     */
    private const EVENTS = [
        // À venir
        ['title' => 'EU Community of Practice', 'type' => EventType::Conference, 'start' => '2026-10-18 09:00', 'end' => null, 'city' => 'Bruxelles', 'country' => 'BE', 'themes' => ['Multilatéralisme', 'Médiation internationale'], 'participants' => ['olivia.caeymaex', 'sophie.lambert', 'claire.dubois', 'delphine.borione', 'marie.joelle.zahar']],
        ['title' => 'Médiation et processus de paix', 'type' => EventType::Workshop, 'start' => '2026-10-26 09:00', 'end' => null, 'city' => 'Dakar', 'country' => 'SN', 'themes' => ['Médiation internationale', 'Femmes, paix et sécurité'], 'participants' => ['aminata.diallo', 'aissatou.bah', 'achta.djibrine.sy']],
        ['title' => 'Écoute active et médiation communautaire', 'type' => EventType::Training, 'start' => '2026-11-04 14:00', 'end' => null, 'city' => null, 'country' => null, 'themes' => ['Médiation communautaire'], 'participants' => ['rokia.coulibaly', 'nathalie.joseph']],
        ['title' => 'Paris Peace Forum', 'type' => EventType::InternationalForum, 'start' => '2026-11-10 09:00', 'end' => '2026-11-11 18:00', 'city' => 'Paris', 'country' => 'FR', 'featured' => true, 'description' => "Le Forum de Paris sur la Paix s’efforce de réinventer la diplomatie dans un monde qui change. Chaque année, il réunit gouvernements, organisations internationales, société civile et entreprises pour faire émerger des réponses concrètes aux défis de la paix et de la coopération internationale.\n\nSa 9e édition, « Joining forces on what matters most », explore les chemins de la souveraineté à la coopération : plus de 350 intervenants et 80 sessions autour de quatre axes (géopolitique, technologies émergentes, développement, planète), avec l’enfance au cœur de l’édition.", 'themes' => ['Multilatéralisme', 'Femmes, paix et sécurité', 'Société civile'], 'participants' => ['delphine.borione', 'kalinda.magloire', 'fatima.maiga', 'esther.omam', 'aminata.diallo', 'claire.dubois', 'olivia.caeymaex', 'sophie.lambert', 'lina.haddad']],
        ['title' => 'Sommet de la Francophonie', 'type' => EventType::Summit, 'start' => '2026-11-18 09:00', 'end' => '2026-11-19 18:00', 'city' => 'Kigali', 'country' => 'RW', 'themes' => ['Multilatéralisme', 'Société civile'], 'participants' => ['fatou.ndiaye', 'nadia.benali', 'hanane.el.amrani', 'grace.uwimana']],
        ['title' => 'Femmes médiatrices dans les Grands Lacs', 'type' => EventType::Conference, 'start' => '2026-11-26 09:00', 'end' => null, 'city' => 'Bujumbura', 'country' => 'BI', 'themes' => ['Femmes, paix et sécurité', 'Prévention des conflits'], 'participants' => ['andree.niyonsaba', 'grace.uwimana', 'esther.mukendi', 'nelly.godelive.mbangu', 'catherine.samba.panza']],
        ['title' => 'Atelier régional', 'type' => EventType::Workshop, 'start' => '2026-12-05 09:00', 'end' => null, 'city' => 'Rabat', 'country' => 'MA', 'themes' => ['Gouvernance locale', 'Médiation communautaire'], 'participants' => ['leila.bouzid', 'josiane.mbarga', 'esther.mukendi']],
        ['title' => 'Dialogue interreligieux et cohésion sociale', 'type' => EventType::Training, 'start' => '2027-01-14 15:00', 'end' => null, 'city' => null, 'country' => null, 'themes' => ['Dialogue interreligieux', 'Cohésion sociale'], 'participants' => ['fatou.ndiaye', 'mireille.kouassi']],
        ['title' => 'Forum de Genève sur la médiation', 'type' => EventType::InternationalForum, 'start' => '2027-02-09 09:00', 'end' => '2027-02-11 17:00', 'city' => 'Genève', 'country' => 'CH', 'themes' => ['Médiation internationale', 'Policy & plaidoyer'], 'participants' => ['olivia.caeymaex', 'claire.dubois', 'isabelle.moreau', 'marie.claude.tremblay', 'lina.haddad', 'sarah.mensah']],
        // Passés
        ['title' => 'Journée internationale de la paix', 'type' => EventType::Conference, 'start' => '2026-09-21 09:00', 'end' => null, 'city' => 'Abidjan', 'country' => 'CI', 'themes' => ['Cohésion sociale', 'Jeunesse et paix'], 'participants' => ['fatou.ndiaye', 'mireille.kouassi', 'marguerite.yoli.bi.kone', 'aminata.diallo', 'rokia.coulibaly']],
        ['title' => 'Médiation et justice transitionnelle', 'type' => EventType::Workshop, 'start' => '2026-09-08 09:00', 'end' => '2026-09-09 17:00', 'city' => 'Tunis', 'country' => 'TN', 'themes' => ['Médiation et justice'], 'participants' => ['leila.bouzid', 'hanane.el.amrani', 'mariam.keita']],
        ['title' => 'Rédiger un accord de paix', 'type' => EventType::Training, 'start' => '2026-06-17 14:00', 'end' => null, 'city' => null, 'country' => null, 'themes' => ['Recherche et formation', 'Médiation internationale'], 'participants' => ['sarah.mensah', 'christine.rakoto']],
        ['title' => 'Sommet des femmes leaders africaines', 'type' => EventType::Summit, 'start' => '2026-05-12 09:00', 'end' => '2026-05-13 18:00', 'city' => 'Kinshasa', 'country' => 'CD', 'themes' => ['Femmes, paix et sécurité', 'Gouvernance locale'], 'participants' => ['esther.mukendi', 'nelly.godelive.mbangu', 'catherine.samba.panza', 'josiane.mbarga', 'achta.djibrine.sy', 'esther.omam']],
    ];

    /**
     * Résumé d'une ligne de chaque événement (accueil, fiche d'une médiatrice), par titre.
     *
     * @var array<string, string>
     */
    private const SUMMARIES = [
        'EU Community of Practice' => 'Les praticiennes européennes de la médiation partagent leurs expériences et leurs méthodes.',
        'Médiation et processus de paix' => 'Un atelier pratique sur la place des femmes dans les processus de paix en Afrique de l’Ouest.',
        'Écoute active et médiation communautaire' => 'Une formation en ligne aux techniques d’écoute active au service de la médiation de proximité.',
        'Paris Peace Forum' => 'Le rendez-vous international qui réinvente la diplomatie, de la souveraineté à la coopération.',
        'Sommet de la Francophonie' => 'Le rendez-vous des chefs d’État et de gouvernement de l’espace francophone, à Kigali.',
        'Femmes médiatrices dans les Grands Lacs' => 'Des médiatrices engagées dans la prévention des conflits de la région des Grands Lacs partagent leur expérience.',
        'Atelier régional' => 'Une journée de travail sur la médiation communautaire et la gouvernance locale au Maghreb.',
        'Dialogue interreligieux et cohésion sociale' => 'Une formation en ligne aux outils du dialogue interreligieux pour renforcer la cohésion sociale.',
        'Forum de Genève sur la médiation' => 'Trois jours d’échanges entre médiatrices, diplomates et chercheuses sur la médiation internationale.',
        'Journée internationale de la paix' => 'Une journée de rencontres pour célébrer la paix et l’engagement des jeunes à Abidjan.',
        'Médiation et justice transitionnelle' => 'Deux jours d’atelier sur l’articulation entre médiation et justice transitionnelle.',
        'Rédiger un accord de paix' => 'Une formation en ligne aux étapes clés de la rédaction d’un accord de paix.',
        'Sommet des femmes leaders africaines' => 'Des femmes leaders du continent réunies autour de la gouvernance et de la sécurité.',
    ];

    /**
     * Fiche détaillée des événements qui en ont une, par titre : salle, organisateur, liens,
     * photo (event-photos/<slug>.webp, copiée dans le stockage du tenant), rôle, jours de
     * présence (absents = tous) et note des participantes, programme (heures de Paris ;
     * speaker = adresse sans @example.org).
     *
     * Paris Peace Forum : informations publiques de parispeaceforum.org (édition 2026). Le
     * programme officiel n'étant pas encore publié, celui-ci est indicatif.
     *
     * @var array<string, array{venue?: string, organizer_name?: string, organizer_detail?: string, languages?: string, links?: list<array{label: string, url: string}>, photo?: string, attendees?: array<string, array{role?: EventRole, days?: list<string>, note?: string}>, sessions?: list<array{start: string, title: string, detail?: string, speaker?: string, network?: bool}>}>
     */
    private const PAGES = [
        'Paris Peace Forum' => [
            'venue' => 'Palais de Chaillot, Trocadéro',
            'organizer_name' => 'Forum de Paris sur la Paix',
            'organizer_detail' => 'Association loi 1901, Paris',
            'links' => [
                ['label' => 'Site officiel', 'url' => 'https://parispeaceforum.org/fr/'],
                ['label' => 'Édition 2026', 'url' => 'https://parispeaceforum.org/2026-forum-homepage/'],
                ['label' => 'Accréditations presse', 'url' => 'https://parispeaceforum.org/accreditations/'],
            ],
            'photo' => 'paris-peace-forum-2026',
            'attendees' => [
                'aminata.diallo' => ['role' => EventRole::Speaker],
                'claire.dubois' => ['days' => ['2026-11-10']],
                'sophie.lambert' => ['note' => 'Disponible en soirée pour échanger'],
                'lina.haddad' => ['days' => ['2026-11-11']],
            ],
            'sessions' => [
                ['start' => '2026-11-10 09:00', 'title' => 'Ouverture et plénière inaugurale', 'detail' => 'Salle plénière'],
                ['start' => '2026-11-10 11:00', 'title' => 'De la souveraineté à la coopération', 'detail' => 'Table ronde'],
                ['start' => '2026-11-10 14:30', 'title' => 'Femmes médiatrices et diplomatie de la paix', 'detail' => 'Panel', 'speaker' => 'aminata.diallo', 'network' => true],
                ['start' => '2026-11-10 17:00', 'title' => 'L’enfance au cœur de la paix', 'detail' => 'Session spéciale'],
                ['start' => '2026-11-11 09:30', 'title' => 'Société civile et prévention des conflits', 'detail' => 'Atelier'],
                ['start' => '2026-11-11 12:30', 'title' => 'Déjeuner des médiatrices francophones', 'detail' => 'Rencontre du réseau RIMeF · sur inscription', 'network' => true],
                ['start' => '2026-11-11 16:00', 'title' => 'Clôture du forum', 'detail' => 'Salle plénière'],
            ],
        ],
    ];

    public function run(): void
    {
        $themes = Expertise::query()->pluck('id', 'name');

        foreach (self::EVENTS as $data) {
            $page = self::PAGES[$data['title']] ?? [];

            $event = Event::query()->updateOrCreate(['title' => $data['title']], [
                'type' => $data['type'],
                'starts_at' => Carbon::parse($data['start'], 'Europe/Paris')->utc(),
                'ends_at' => $data['end'] === null ? null : Carbon::parse($data['end'], 'Europe/Paris')->utc(),
                'city' => $data['city'],
                'venue' => $page['venue'] ?? null,
                'country_code' => $data['country'],
                'is_online' => $data['country'] === null,
                'is_featured' => $data['featured'] ?? false,
                'description' => $data['description'] ?? null,
                'summary' => self::SUMMARIES[$data['title']] ?? null,
                'organizer_name' => $page['organizer_name'] ?? null,
                'organizer_detail' => $page['organizer_detail'] ?? null,
                'languages' => $page['languages'] ?? null,
                'links' => $page['links'] ?? null,
            ]);

            $event->themes()->sync(collect($data['themes'])->map(fn (string $name): ?int => $themes[$name] ?? null)->filter());

            $members = $this->members($data['participants']);
            $event->participants()->sync($members->mapWithKeys(fn (int $id, string $name): array => [$id => [
                'role' => ($page['attendees'][$name]['role'] ?? EventRole::Participant)->value,
                'attendance_days' => $page['attendees'][$name]['days'] ?? null,
                'attendance_note' => $page['attendees'][$name]['note'] ?? null,
            ]])->all());

            $this->syncSessions($event, $page['sessions'] ?? []);

            if (isset($page['photo'])) {
                $this->attachPhoto($event, $page['photo']);
            }
        }
    }

    /**
     * Comptes des participantes, par adresse sans @example.org.
     *
     * @param  list<string>  $names
     * @return Collection<string, int>
     */
    private function members(array $names): Collection
    {
        return User::query()
            ->whereIn('email', array_map(fn (string $name): string => "{$name}@example.org", $names))
            ->pluck('id', 'email')
            ->mapWithKeys(fn (int $id, string $email): array => [Str::before($email, '@') => $id]);
    }

    /**
     * Remplace le programme de l'événement.
     *
     * @param  list<array{start: string, title: string, detail?: string, speaker?: string, network?: bool}>  $sessions
     */
    private function syncSessions(Event $event, array $sessions): void
    {
        $speakers = $this->members(array_values(array_filter(array_column($sessions, 'speaker'))));

        $event->sessions()->delete();
        foreach ($sessions as $session) {
            $event->sessions()->create([
                'starts_at' => Carbon::parse($session['start'], 'Europe/Paris')->utc(),
                'title' => $session['title'],
                'detail' => $session['detail'] ?? null,
                'speaker_id' => isset($session['speaker']) ? $speakers[$session['speaker']] ?? null : null,
                'is_network' => $session['network'] ?? false,
            ]);
        }
    }

    /**
     * Photo de l'événement, si event-photos/<slug>.webp existe (visuels de tiers exclus de git) :
     * copiée dans le stockage privé du tenant.
     */
    private function attachPhoto(Event $event, string $slug): void
    {
        $source = database_path("seeders/event-photos/{$slug}.webp");

        if (! is_file($source)) {
            return;
        }

        $path = "events/{$event->id}.webp";
        $contents = (string) file_get_contents($source);
        $disk = Storage::disk('local');

        if ($event->photo_path === $path && $disk->exists($path) && $disk->get($path) === $contents) {
            return;
        }

        $disk->put($path, $contents);
        // Nouvelle date de mise à jour = nouvelle URL de la photo : le cache navigateur est renouvelé
        $event->photo_path = $path;
        $event->touch();
    }
}
