<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventType;
use App\Enums\Region;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\DirectoryCatalog;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Événement du réseau (forum, sommet, atelier…) de l'Agenda : sur place (ville, pays) ou en ligne.
 *
 * Le slug (titre-année, unique) sert d'adresse à la fiche : /agenda/paris-peace-forum-2026.
 */
#[Fillable([
    'title', 'type', 'starts_at', 'ends_at', 'city', 'venue', 'country_code', 'is_online', 'description', 'summary', 'photo_path',
    'is_featured', 'organizer_name', 'organizer_detail', 'languages', 'links',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUniqueSlug;

    /** Valeur du filtre « Région » pour les événements en ligne (aux côtés des régions du monde). */
    public const ONLINE_REGION = 'online';

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_online' => 'boolean',
            'is_featured' => 'boolean',
            'links' => 'array',
        ];
    }

    /**
     * Médiatrices inscrites, avec leur rôle (EventRole), leurs jours de présence (JSON, null = tous),
     * leur note facultative et leur proposition de se retrouver sur place.
     *
     * @return BelongsToMany<User, $this, EventParticipant>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(EventParticipant::class)
            ->withPivot(['role', 'attendance_days', 'attendance_note', 'wants_to_meet']);
    }

    /**
     * Programme, dans l'ordre chronologique.
     *
     * @return HasMany<EventSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class)->orderBy('starts_at')->orderBy('id');
    }

    /**
     * Ressources de la bibliothèque présentées ou discutées lors de l'événement.
     *
     * @return HasMany<\App\Models\Resource, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Thématiques, dans le vocabulaire des expertises des médiatrices.
     *
     * @return BelongsToMany<Expertise, $this>
     */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Expertise::class);
    }

    /**
     * Slug : titre et année de début, les éditions successives d'un même événement restant distinctes.
     */
    protected function slugSource(): string
    {
        return "{$this->title} {$this->starts_at?->year}";
    }

    protected function slugFallback(): string
    {
        return 'evenement';
    }

    protected function slugDependsOn(): array
    {
        return ['title', 'starts_at'];
    }

    /**
     * Lieu affiché : « Ville, Pays » ou « En ligne ».
     */
    public function placeLabel(): ?string
    {
        if ($this->is_online) {
            return 'En ligne';
        }

        $place = implode(', ', array_filter([$this->city, DirectoryCatalog::countryName($this->country_code)]));

        return $place === '' ? null : $place;
    }

    public function region(): ?Region
    {
        return $this->is_online ? null : DirectoryCatalog::regionOf($this->country_code);
    }

    /**
     * Terminé : sa fin (ou, à défaut, son début) est passée.
     */
    public function isPast(): bool
    {
        return ($this->ends_at ?? $this->starts_at)->isPast();
    }

    /**
     * Jours couverts par l'événement dans le fuseau $timezone (« 2026-11-10 », « 2026-11-11 »).
     *
     * @return list<string>
     */
    public function days(string $timezone = 'UTC'): array
    {
        $day = CarbonImmutable::instance($this->starts_at)->setTimezone($timezone)->startOfDay();
        $last = CarbonImmutable::instance($this->ends_at ?? $this->starts_at)->setTimezone($timezone)->startOfDay();

        $days = [];
        for (; $day <= $last; $day = $day->addDay()) {
            $days[] = $day->toDateString();
        }

        return $days;
    }

    /**
     * Commencé et pas encore terminé.
     */
    public function isOngoing(): bool
    {
        return $this->starts_at->isPast() && ! $this->isPast();
    }

    /**
     * URL de la photo (route protégée), versionnée par la date de mise à jour pour un cache
     * navigateur long ; null sans photo.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path === null ? null : route('events.photo', [
            'event' => $this->id,
            'v' => $this->updated_at?->getTimestamp(),
        ], false);
    }
}
