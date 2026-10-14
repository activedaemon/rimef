<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Slug unique servant d'adresse à une fiche (/reseau/aminata-diallo, /agenda/paris-peace-forum-2026) :
 * calculé à l'enregistrement, puis recalculé quand ses attributs sources changent. Un homonyme
 * reçoit le premier suffixe libre (-2, -3…).
 *
 * @mixin Model
 */
trait HasUniqueSlug
{
    /**
     * Texte dont le slug est tiré (sans accents ni ponctuation).
     */
    abstract protected function slugSource(): string;

    /**
     * Slug de secours quand le texte n'a aucun caractère utilisable.
     */
    abstract protected function slugFallback(): string;

    /**
     * Attributs qui, modifiés, font recalculer le slug ; liste vide = slug figé à la création.
     *
     * @return list<string>
     */
    abstract protected function slugDependsOn(): array;

    protected static function bootHasUniqueSlug(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&HasUniqueSlug $model */
            $sources = $model->slugDependsOn();

            // isDirty([]) vaudrait « un attribut quelconque a changé »
            if ($model->slug === null || ($sources !== [] && $model->isDirty($sources))) {
                $model->slug = $model->uniqueSlug();
            }
        });
    }

    private function uniqueSlug(): string
    {
        $base = Str::slug($this->slugSource()) ?: $this->slugFallback();

        $taken = static::query()
            ->whereKeyNot($this->getKey())
            ->where(fn (Builder $query) => $query->where('slug', $base)->orWhere('slug', 'like', "{$base}-%"))
            ->pluck('slug')
            ->flip();

        $slug = $base;
        for ($suffix = 2; $taken->has($slug); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
