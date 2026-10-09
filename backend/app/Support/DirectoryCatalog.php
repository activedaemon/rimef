<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Region;
use Illuminate\Support\Str;

/**
 * Pays de l'annuaire et leur région (config/directory.php).
 */
class DirectoryCatalog
{
    /**
     * @return array<string, array{name: string, region: string}>
     */
    public static function countries(): array
    {
        return config('directory.countries');
    }

    public static function countryName(?string $code): ?string
    {
        return $code === null ? null : (self::countries()[$code]['name'] ?? $code);
    }

    public static function regionOf(?string $code): ?Region
    {
        $region = $code === null ? null : (self::countries()[$code]['region'] ?? null);

        return $region === null ? null : Region::from($region);
    }

    /**
     * Codes des pays d'une ou plusieurs régions.
     *
     * @param  list<string>  $regions
     * @return list<string>
     */
    public static function countryCodesIn(array $regions): array
    {
        return array_keys(array_filter(
            self::countries(),
            fn (array $country): bool => in_array($country['region'], $regions, true),
        ));
    }

    /**
     * Codes dont le libellé contient le mot cherché (sans tenir compte des accents ni de la casse).
     *
     * @param  array<string, string>  $labels  code => libellé
     * @return list<string>
     */
    public static function codesMatching(array $labels, string $word): array
    {
        $needle = self::normalize($word);

        return array_keys(array_filter(
            $labels,
            fn (string $label): bool => str_contains(self::normalize($label), $needle),
        ));
    }

    /**
     * @return array<string, string> code => nom du pays
     */
    public static function countryNames(): array
    {
        return array_map(fn (array $country): string => $country['name'], self::countries());
    }

    private static function normalize(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }
}
