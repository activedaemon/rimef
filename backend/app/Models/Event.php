<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventType;
use App\Enums\Region;
use App\Support\DirectoryCatalog;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Événement du réseau (forum, sommet, atelier…) de l'Agenda : sur place (ville, pays) ou en ligne.
 */
#[Fillable(['title', 'type', 'starts_at', 'ends_at', 'city', 'country_code', 'is_online', 'description', 'is_featured'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

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
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
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
}
