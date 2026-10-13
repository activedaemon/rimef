<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Region;
use App\Enums\ResourceType;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Ressources de la bibliothèque reprises de la maquette, complétées pour la pagination : en
 * environnement local uniquement (TenantDatabaseSeeder), après DemoMembersSeeder,
 * DemoEventsSeeder et ExpertiseSeeder. Relançable : les ressources sont retrouvées par leur titre.
 */
class DemoResourcesSeeder extends Seeder
{
    /**
     * Source = organisation (null quand l'autrice est une médiatrice) ; author et members =
     * adresses de DemoMembersSeeder, sans @example.org ; region null = Monde ; event = titre
     * d'un événement de DemoEventsSeeder ; thématiques = noms d'expertises.
     *
     * @var list<array{title: string, type: ResourceType, source?: string, author?: string, date: string, duration?: int, region: Region|null, languages: list<string>, themes: list<string>, members?: list<string>, event?: string, url?: string}>
     */
    private const RESOURCES = [
        // Maquette
        ['title' => 'Women Mediators and Inclusive Peace Processes', 'type' => ResourceType::Report, 'source' => 'Conciliation Resources', 'date' => '2026-04-14', 'region' => Region::WestAfrica, 'languages' => ['fr', 'en'], 'themes' => ['Femmes, paix et sécurité', 'Médiation internationale'], 'members' => ['aminata.diallo', 'fatou.ndiaye'], 'event' => 'Paris Peace Forum', 'url' => 'https://www.c-r.org/'],
        ['title' => 'Médiation locale et résilience communautaire', 'type' => ResourceType::Article, 'source' => 'International Alert', 'date' => '2026-01-20', 'region' => Region::WestAfrica, 'languages' => ['fr'], 'themes' => ['Gouvernance locale', 'Médiation communautaire'], 'url' => 'https://www.international-alert.org/fr/'],
        ['title' => 'Paroles de médiatrices : du local au multilatéral', 'type' => ResourceType::Podcast, 'source' => 'RIMeF', 'date' => '2025-12-09', 'duration' => 34, 'region' => null, 'languages' => ['fr'], 'themes' => ['Femmes, paix et sécurité', 'Multilatéralisme'], 'members' => ['aminata.diallo']],
        ['title' => 'Femmes, paix et sécurité : quels leviers pour agir ?', 'type' => ResourceType::Video, 'source' => 'ONU Femmes', 'date' => '2025-11-20', 'duration' => 28, 'region' => null, 'languages' => ['fr', 'en'], 'themes' => ['Femmes, paix et sécurité', 'Policy & plaidoyer'], 'url' => 'https://www.unwomen.org/fr'],
        ['title' => 'Ce que le Paris Peace Forum 2025 a retenu des médiatrices', 'type' => ResourceType::Article, 'author' => 'aminata.diallo', 'date' => '2025-11-18', 'region' => Region::Europe, 'languages' => ['fr'], 'themes' => ['Multilatéralisme', 'Femmes, paix et sécurité'], 'event' => 'Paris Peace Forum'],
        ['title' => 'Guide pratique de la médiation communautaire', 'type' => ResourceType::Tool, 'source' => 'RIMeF', 'date' => '2025-10-06', 'region' => Region::GreatLakes, 'languages' => ['fr'], 'themes' => ['Médiation communautaire'], 'members' => ['andree.niyonsaba']],
        ['title' => 'Médiation et justice transitionnelle au Mali', 'type' => ResourceType::Analysis, 'author' => 'mariam.keita', 'date' => '2025-09-15', 'region' => Region::WestAfrica, 'languages' => ['fr'], 'themes' => ['Médiation et justice', 'Prévention des conflits']],
        ['title' => 'Bilan annuel du réseau : 124 médiatrices, 18 rencontres', 'type' => ResourceType::Report, 'source' => 'RIMeF', 'date' => '2025-07-02', 'region' => null, 'languages' => ['fr', 'en'], 'themes' => ['Société civile']],
        // Compléments
        ['title' => 'Prévenir les conflits liés au foncier au Sahel', 'type' => ResourceType::Analysis, 'source' => 'Institut d’études de sécurité', 'date' => '2026-09-03', 'region' => Region::WestAfrica, 'languages' => ['fr'], 'themes' => ['Prévention des conflits', 'Gouvernance locale'], 'url' => 'https://issafrica.org/fr'],
        ['title' => 'Écouter pour apaiser : la médiation de proximité', 'type' => ResourceType::Podcast, 'source' => 'RIMeF', 'date' => '2026-08-21', 'duration' => 41, 'region' => Region::CentralAfrica, 'languages' => ['fr'], 'themes' => ['Médiation communautaire', 'Cohésion sociale'], 'members' => ['josiane.mbarga', 'esther.mukendi']],
        ['title' => 'Jeunes et artisans de paix : retours d’expérience', 'type' => ResourceType::Video, 'source' => 'UNESCO', 'date' => '2026-07-10', 'duration' => 19, 'region' => null, 'languages' => ['fr', 'en', 'es'], 'themes' => ['Jeunesse et paix'], 'url' => 'https://www.unesco.org/fr'],
        ['title' => 'Dialogue interreligieux : une boîte à outils pour les médiatrices', 'type' => ResourceType::Tool, 'source' => 'KAICIID', 'date' => '2026-06-02', 'region' => Region::MiddleEast, 'languages' => ['fr', 'ar', 'en'], 'themes' => ['Dialogue interreligieux', 'Cohésion sociale'], 'members' => ['lina.haddad']],
        ['title' => 'Transitions politiques et dialogue inclusif en Afrique centrale', 'type' => ResourceType::Report, 'source' => 'International Crisis Group', 'date' => '2026-05-18', 'region' => Region::CentralAfrica, 'languages' => ['fr', 'en'], 'themes' => ['Dialogue inclusif', 'Gouvernance locale'], 'url' => 'https://www.crisisgroup.org/fr'],
        ['title' => 'Médiatrices des Grands Lacs : paroles de terrain', 'type' => ResourceType::Article, 'author' => 'andree.niyonsaba', 'date' => '2026-03-27', 'region' => Region::GreatLakes, 'languages' => ['fr'], 'themes' => ['Femmes, paix et sécurité', 'Prévention des conflits'], 'members' => ['grace.uwimana']],
        ['title' => 'La médiation électorale, mode d’emploi', 'type' => ResourceType::Tool, 'source' => 'Organisation internationale de la Francophonie', 'date' => '2026-02-12', 'region' => null, 'languages' => ['fr'], 'themes' => ['Gouvernance locale', 'Prévention des conflits'], 'event' => 'Médiation électorale', 'url' => 'https://www.francophonie.org/'],
        ['title' => 'Médiation et changement climatique : nouveaux conflits, nouvelles réponses', 'type' => ResourceType::Analysis, 'author' => 'marie.claude.tremblay', 'date' => '2025-12-01', 'region' => Region::NorthAmerica, 'languages' => ['fr', 'en'], 'themes' => ['Prévention des conflits', 'Policy & plaidoyer']],
        ['title' => 'Cohésion sociale en Haïti : leçons de la médiation communautaire', 'type' => ResourceType::Report, 'source' => 'Programme des Nations unies pour le développement', 'date' => '2025-08-25', 'region' => Region::LatinAmerica, 'languages' => ['fr'], 'themes' => ['Cohésion sociale', 'Médiation communautaire'], 'members' => ['kalinda.magloire'], 'url' => 'https://www.undp.org/fr'],
        ['title' => 'Négocier la paix : les femmes à la table', 'type' => ResourceType::Video, 'source' => 'Swisspeace', 'date' => '2025-06-16', 'duration' => 52, 'region' => Region::Europe, 'languages' => ['fr', 'en'], 'themes' => ['Femmes, paix et sécurité', 'Médiation internationale'], 'members' => ['claire.dubois']],
        ['title' => 'Société civile et processus de paix au Maghreb', 'type' => ResourceType::Article, 'author' => 'leila.bouzid', 'date' => '2025-04-08', 'region' => Region::NorthAfrica, 'languages' => ['fr', 'ar'], 'themes' => ['Société civile', 'Dialogue inclusif']],
        ['title' => 'Former les médiatrices de demain', 'type' => ResourceType::Podcast, 'source' => 'RIMeF', 'date' => '2025-02-19', 'duration' => 27, 'region' => null, 'languages' => ['fr'], 'themes' => ['Recherche et formation'], 'members' => ['sarah.mensah', 'christine.rakoto']],
    ];

    public function run(): void
    {
        $themes = Expertise::query()->pluck('id', 'name');

        foreach (self::RESOURCES as $data) {
            $members = $this->members([...($data['members'] ?? []), ...(isset($data['author']) ? [$data['author']] : [])]);

            $resource = Resource::query()->updateOrCreate(['title' => $data['title']], [
                'type' => $data['type'],
                'source' => $data['source'] ?? null,
                'author_id' => isset($data['author']) ? $members[$data['author']] ?? null : null,
                'published_on' => $data['date'],
                'duration_minutes' => $data['duration'] ?? null,
                'region' => $data['region'],
                'languages' => $data['languages'],
                'url' => $data['url'] ?? null,
                'event_id' => isset($data['event']) ? Event::query()->where('title', $data['event'])->value('id') : null,
            ]);

            $resource->themes()->sync(collect($data['themes'])->map(fn (string $name): ?int => $themes[$name] ?? null)->filter());
            $resource->members()->sync($members->only($data['members'] ?? [])->values());
        }
    }

    /**
     * Comptes des médiatrices, par adresse sans @example.org.
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
}
