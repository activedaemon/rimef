<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Expertise;
use Illuminate\Database\Seeder;

/**
 * Liste de référence des expertises (reprise de la maquette), dans tous les environnements.
 * Relançable : n'ajoute que les expertises manquantes.
 */
class ExpertiseSeeder extends Seeder
{
    public const EXPERTISES = [
        'Cohésion sociale',
        'Dialogue inclusif',
        'Dialogue interreligieux',
        'Femmes, paix et sécurité',
        'Gouvernance locale',
        'Jeunesse et paix',
        'Médiation communautaire',
        'Médiation et justice',
        'Médiation internationale',
        'Multilatéralisme',
        'Policy & plaidoyer',
        'Prévention des conflits',
        'Recherche et formation',
        'Société civile',
    ];

    public function run(): void
    {
        foreach (self::EXPERTISES as $name) {
            Expertise::firstOrCreate(['name' => $name]);
        }
    }
}
