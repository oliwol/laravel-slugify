<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSlugHistory
{
    public static function findBySlugWithHistory(string $slug): ?static
    {
        $found = static::findBySlug($slug);

        if ($found !== null) {
            return $found;
        }

        $instance = new static;

        $history = SlugHistory::query()
            ->where('slug', $slug)
            ->where('sluggable_type', $instance->getMorphClass())
            ->first();

        if (! $history instanceof SlugHistory) {
            return null;
        }

        return $instance->newQuery()
            ->tap(fn (Builder $query) => $instance->scopeSlugQuery($query))
            ->where($instance->getKeyName(), $history->getAttribute('sluggable_id'))
            ->first();
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        /** @var string $slugColumn */
        $slugColumn = $this->getAttributeToSaveSlugTo(); // @phpstan-ignore method.notFound

        $resolveBySlug = $field === null
            ? $this->getRouteKeyName() === $slugColumn
            : $field === $slugColumn;

        if ($resolveBySlug) {
            return static::findBySlugWithHistory((string) $value);
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * @return MorphMany<SlugHistory, $this>
     */
    public function slugHistory(): MorphMany
    {
        return $this->morphMany(SlugHistory::class, 'sluggable');
    }

    protected static function bootHasSlugHistory(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&HasSlug&HasSlugHistory $model */
            $target = $model->getAttributeToSaveSlugTo();
            $originalSlug = $model->getOriginal($target);

            if (! filled($originalSlug)) {
                return;
            }

            $newSlug = $model->getAttribute($target);

            if ($originalSlug === $newSlug) {
                return;
            }

            $exists = SlugHistory::query()
                ->where('slug', $originalSlug)
                ->where('sluggable_type', $model->getMorphClass())
                ->where('sluggable_id', $model->getKey())
                ->exists();

            if ($exists) {
                return;
            }

            SlugHistory::query()->create([
                'slug' => $originalSlug,
                'sluggable_type' => $model->getMorphClass(),
                'sluggable_id' => $model->getKey(),
                'created_at' => now(),
            ]);
        });
    }
}
