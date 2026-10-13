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

/**
 * Ressource de la bibliothèque (rapport, analyse, podcast…), publiée par une organisation
 * (source) ou écrite par une médiatrice du réseau (author).
 *
 * Le slug (titre, unique) servira d'adresse à la fiche : /ressources/women-mediators-and-inclusive-peace-processes.
 */
#[Fillable([
    'type', 'title', 'summary', 'source', 'author_id', 'published_on', 'duration_minutes', 'region', 'languages', 'url', 'event_id',
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
     * Médiatrices du réseau liées : autrices, contributrices ou recommandations.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resource_member');
    }

    /**
     * Comptes qui l'ont enregistrée (signet).
     *
     * @return BelongsToMany<User, $this>
     */
    public function savers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resource_saves')->withTimestamps();
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
