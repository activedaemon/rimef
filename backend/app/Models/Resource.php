<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Region;
use App\Enums\ResourceType;
use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ressource de la bibliothèque (rapport, analyse, podcast…), publiée par une organisation
 * (source) ou écrite par une médiatrice du réseau (author).
 *
 * Le slug (titre, unique) servira d'adresse à la fiche : /ressources/women-mediators-and-inclusive-peace-processes.
 */
#[Fillable([
    'type', 'title', 'summary', 'source', 'source_detail', 'source_url', 'author_id', 'published_on', 'duration_minutes', 'pages',
    'region', 'languages', 'url', 'event_id', 'event_note',
])]
class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory, HasUniqueSlug;

    /** Valeur du filtre « Région » pour les ressources de portée mondiale (sans région). */
    public const WORLD_REGION = 'world';

    /**
     * Langues proposées, par code ISO 639-1.
     *
     * @var array<string, string>
     */
    public const LANGUAGES = [
        'fr' => 'Français',
        'en' => 'Anglais',
        'ar' => 'Arabe',
        'es' => 'Espagnol',
        'pt' => 'Portugais',
    ];

    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'published_on' => 'date',
            'region' => Region::class,
            'languages' => 'array',
        ];
    }

    /**
     * Médiatrice du réseau qui l'a écrite.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Événement de l'Agenda où elle a été présentée ou discutée.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
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
     * Médiatrices du réseau liées (contributrices ou citées), avec leur rôle (ResourceMemberRole)
     * et la note qui explique le lien.
     *
     * @return BelongsToMany<User, $this, ResourceMember>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resource_member')
            ->using(ResourceMember::class)
            ->withPivot(['role', 'note']);
    }

    /**
     * Comptes qui l'ont mise en favoris (marque-page).
     *
     * @return BelongsToMany<User, $this>
     */
    public function favoredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resource_favorites')->withTimestamps();
    }

    /**
     * Avis des médiatrices.
     *
     * @return HasMany<ResourceRecommendation, $this>
     */
    public function recommendations(): HasMany
    {
        return $this->hasMany(ResourceRecommendation::class);
    }

    protected function slugSource(): string
    {
        return $this->title;
    }

    protected function slugFallback(): string
    {
        return 'ressource';
    }

    protected function slugDependsOn(): array
    {
        return ['title'];
    }
}
