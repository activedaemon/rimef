<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

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
        ['title' => 'Paris Peace Forum', 'type' => EventType::InternationalForum, 'start' => '2026-11-12 09:00', 'end' => '2026-11-13 18:00', 'city' => 'Paris', 'country' => 'FR', 'featured' => true, 'description' => 'Un espace de dialogue pour des solutions multilatérales aux défis globaux.', 'themes' => ['Multilatéralisme', 'Femmes, paix et sécurité', 'Société civile'], 'participants' => ['delphine.borione', 'kalinda.magloire', 'fatima.maiga', 'esther.omam', 'aminata.diallo', 'claire.dubois', 'olivia.caeymaex', 'sophie.lambert', 'lina.haddad']],
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

    public function run(): void
    {
        $themes = Expertise::query()->pluck('id', 'name');

        foreach (self::EVENTS as $data) {
            $event = Event::query()->updateOrCreate(['title' => $data['title']], [
                'type' => $data['type'],
                'starts_at' => Carbon::parse($data['start'], 'Europe/Paris')->utc(),
                'ends_at' => $data['end'] === null ? null : Carbon::parse($data['end'], 'Europe/Paris')->utc(),
                'city' => $data['city'],
                'country_code' => $data['country'],
                'is_online' => $data['country'] === null,
                'is_featured' => $data['featured'] ?? false,
                'description' => $data['description'] ?? null,
            ]);

            $event->themes()->sync(collect($data['themes'])->map(fn (string $name): ?int => $themes[$name] ?? null)->filter());
            $event->participants()->sync(User::query()
                ->whereIn('email', array_map(fn (string $name): string => "{$name}@example.org", $data['participants']))
                ->pluck('id'));
        }
    }
}
