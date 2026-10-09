<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Événements repris de la maquette, avec des participantes de l'annuaire : en environnement
 * local uniquement (TenantDatabaseSeeder), après DemoMembersSeeder. Relançable : les événements sont retrouvés par leur titre.
 */
class DemoEventsSeeder extends Seeder
{
    /**
     * titre, début et fin (heure de Paris), lieu, à la une, description, participantes
     * (adresses de DemoMembersSeeder, sans @example.org)
     *
     * @var list<array{0: string, 1: string, 2: string|null, 3: string, 4: bool, 5: string|null, 6: list<string>}>
     */
    private const EVENTS = [
        ['EU Community of Practice', '2026-10-18 09:00', null, 'Bruxelles, Belgique', false, null, ['olivia.caeymaex', 'sophie.lambert', 'claire.dubois', 'delphine.borione', 'marie.joelle.zahar']],
        ['Médiation et processus de paix', '2026-10-26 09:00', null, 'Dakar, Sénégal', false, null, ['aminata.diallo', 'aissatou.bah', 'achta.djibrine.sy']],
        ['Paris Peace Forum', '2026-11-12 09:00', '2026-11-13 18:00', 'Paris, France', true, 'Un espace de dialogue pour des solutions multilatérales aux défis globaux.', ['delphine.borione', 'kalinda.magloire', 'fatima.maiga', 'esther.omam', 'aminata.diallo', 'claire.dubois', 'olivia.caeymaex', 'sophie.lambert', 'lina.haddad']],
        ['Sommet de la Francophonie', '2026-11-18 09:00', null, 'Kigali, Rwanda', false, null, ['fatou.ndiaye', 'nadia.benali', 'hanane.el.amrani', 'grace.uwimana']],
        ['Atelier régional', '2026-12-05 09:00', null, 'Rabat, Maroc', false, null, ['leila.bouzid', 'josiane.mbarga', 'esther.mukendi']],
    ];

    public function run(): void
    {
        foreach (self::EVENTS as [$title, $startsAt, $endsAt, $place, $featured, $description, $participants]) {
            $event = Event::query()->updateOrCreate(['title' => $title], [
                'starts_at' => Carbon::parse($startsAt, 'Europe/Paris')->utc(),
                'ends_at' => $endsAt === null ? null : Carbon::parse($endsAt, 'Europe/Paris')->utc(),
                'place' => $place,
                'is_featured' => $featured,
                'description' => $description,
            ]);

            $event->participants()->sync(User::query()
                ->whereIn('email', array_map(fn (string $name): string => "{$name}@example.org", $participants))
                ->pluck('id'));
        }
    }
}
