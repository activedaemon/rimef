<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\Expertise;
use App\Models\MemberProfile;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Page « Modifier mon profil » : enregistrement de l'identité, de la fiche de médiatrice et de sa photo.
 * Un compte sans rôle member (administration) n'a pas de fiche : seuls son prénom, son nom et sa photo changent.
 */
class ProfileEditor
{
    /** Côté du carré enregistré pour une photo (px). */
    public const PHOTO_SIZE = 400;

    private const PHOTO_QUALITY = 82;

    public function hasSheet(User $user): bool
    {
        return $user->hasRole(Role::Member->value);
    }

    /**
     * Fiche à modifier, créée vide au premier enregistrement.
     */
    public function profileOf(User $user): MemberProfile
    {
        return $user->memberProfile()->firstOrNew();
    }

    /**
     * @param  array<string, mixed>  $data  champs validés par UpdateProfileRequest
     */
    public function update(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $user->update(['first_name' => $data['first_name'], 'last_name' => $data['last_name']]);

            if (! $this->hasSheet($user)) {
                return;
            }

            $profile = $this->profileOf($user);
            $profile->fill([
                'job_title' => $data['job_title'] ?? null,
                'organization_type' => $data['organization_type'] ?? null,
                'organization_name' => $data['organization_name'] ?? null,
                'country_code' => $data['country_code'] ?? null,
                'city' => $data['city'] ?? null,
                'tagline' => $data['tagline'] ?? null,
                'bio' => $data['bio'] ?? null,
                'experience_since' => $data['experience_since'] ?? null,
                'audiences' => $data['audiences'] ?? null,
                'is_available' => (bool) ($data['is_available'] ?? false),
                'links' => $this->normalizeLinks($data['links'] ?? []),
            ]);
            // Date de mise à jour affichée sur la page, même quand seules les listes changent
            $profile->updated_at = now();
            $profile->save();

            $this->syncExpertises($profile, $data['expertises'] ?? []);
            $this->syncZones($profile, $data['zones'] ?? []);
        });
    }

    /**
     * Photo recadrée au centre en carré, réduite et réencodée en WebP (métadonnées EXIF retirées).
     */
    public function replacePhoto(User $user, UploadedFile $photo): void
    {
        $profile = $this->profileOf($user);
        $profile->save();

        $path = "members/{$profile->id}.webp";
        Storage::disk('local')->put($path, $this->squareWebp((string) file_get_contents($photo->getRealPath())));

        // Nouvelle date de mise à jour = nouvelle URL de la photo : le cache navigateur est renouvelé
        $profile->photo_path = $path;
        $profile->touch();
    }

    public function removePhoto(User $user): void
    {
        $profile = $user->memberProfile;

        if ($profile?->photo_path === null) {
            return;
        }

        Storage::disk('local')->delete($profile->photo_path);
        $profile->photo_path = null;
        $profile->save();
    }

    /**
     * Listes de choix du formulaire : pays, types d'organisation, expertises du réseau (les plus portées d'abord).
     *
     * @return array{
     *     countries: list<array{code: string, name: string}>,
     *     organization_types: list<array{value: string, label: string}>,
     *     expertises: list<array{name: string, count: int}>
     * }
     */
    public function options(): array
    {
        $countries = collect(DirectoryCatalog::countryNames())
            ->map(fn (string $name, string $code): array => ['code' => $code, 'name' => $name])
            ->sortBy(fn (array $country): string => Str::ascii($country['name']))
            ->values()
            ->all();

        $expertises = Expertise::query()
            ->withCount('memberProfiles')
            ->get()
            ->sortBy(fn (Expertise $expertise): string => Str::ascii($expertise->name))
            ->sortByDesc('member_profiles_count')
            ->map(fn (Expertise $expertise): array => [
                'name' => $expertise->name,
                'count' => $expertise->member_profiles_count,
            ])
            ->values()
            ->all();

        return [
            'countries' => $countries,
            'organization_types' => collect(OrganizationType::cases())
                ->map(fn (OrganizationType $type): array => ['value' => $type->value, 'label' => $type->label()])
                ->all(),
            'expertises' => $expertises,
        ];
    }

    /**
     * Liens vides retirés ; null quand il n'en reste aucun.
     *
     * @param  array<string, mixed>  $links
     * @return array<string, mixed>|null
     */
    private function normalizeLinks(array $links): ?array
    {
        $normalized = [];

        foreach (array_keys(MemberProfile::LINK_LABELS) as $type) {
            if (filled($links[$type] ?? null)) {
                $normalized[$type] = $links[$type];
            }
        }

        if (filled($links['other']['url'] ?? null)) {
            $normalized['other'] = ['label' => $links['other']['label'], 'url' => $links['other']['url']];
        }

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param  list<string>  $names  expertises existantes, dans l'ordre choisi
     */
    private function syncExpertises(MemberProfile $profile, array $names): void
    {
        $ids = Expertise::query()->whereIn('name', $names)->pluck('id', 'name');

        $profile->expertises()->sync(
            collect($names)->values()->mapWithKeys(fn (string $name, int $position): array => [
                $ids[$name] => ['position' => $position],
            ])->all(),
        );
    }

    /**
     * @param  list<string>  $codes  pays, la base principale en premier
     */
    private function syncZones(MemberProfile $profile, array $codes): void
    {
        $profile->zones()->delete();
        $profile->zones()->createMany(
            collect($codes)->values()->map(fn (string $code, int $position): array => [
                'country_code' => $code,
                'position' => $position,
            ])->all(),
        );
    }

    private function squareWebp(string $contents): string
    {
        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('Image illisible.');
        }

        $side = min(imagesx($source), imagesy($source));
        $square = imagecreatetruecolor(self::PHOTO_SIZE, self::PHOTO_SIZE);
        imagecopyresampled(
            $square,
            $source,
            0,
            0,
            intdiv(imagesx($source) - $side, 2),
            intdiv(imagesy($source) - $side, 2),
            self::PHOTO_SIZE,
            self::PHOTO_SIZE,
            $side,
            $side,
        );

        ob_start();
        imagewebp($square, null, self::PHOTO_QUALITY);

        return (string) ob_get_clean();
    }
}
