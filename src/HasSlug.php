<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Get the attribute name to create the slug from.
     */
    abstract public function getSlugifyKeyName(): string;

    /**
     * Create a slug from the given attribute.
     */
    public function createSlug(): void
    {
        if (! $this->isSluggable()) {
            return;
        }

        $saveSlugFrom = $this->getSlugifyKeyName();
        $saveSlugTo = $this->getRouteKeyName();

        // Only update slug when the source attribute is dirty.
        if (! $this->isDirty($saveSlugFrom)) {
            return;
        }

        // Do not override manually set slugs.
        if (filled($this->{$saveSlugTo}) && $this->getOriginal($saveSlugTo) !== $this->{$saveSlugTo}) {
            return;
        }

        $this->{$saveSlugTo} = $this->incrementsSlugIfExists(
            $this->slugify($this->{$saveSlugFrom})
        );
    }

    /**
     * Increments the slug when the slug is already used.
     * Otherwise, returns the original slug.
     * Currently, no scope is applied when checking for existing slugs.
     *
     * TODO: Extend to allow different increment styles (e.g., appending date, random string, etc.).
     * TODO: Allow extending scope to check for existing slugs (e.g., including soft-deleted models).
     */
    public function incrementsSlugIfExists(string $slug): string
    {
        $original = $slug;
        $count = 1;

        while (
        $this
            ->newQueryWithoutScopes()
            ->where($this->getRouteKeyName(), $slug)
            ->whereNot($this->getKeyName(), $this->getKey())
            ->exists()
        ) {
            $slug = $original.($count > 1 ? '-'.$count : '');
            $count++;
        }

        return $slug;
    }

    /**
     * Determine if the model is sluggable.
     */
    public function isSluggable(): bool
    {
        return $this->getRouteKeyName() !== $this->getKeyName() && $this->hasAttribute($this->getSlugifyKeyName());
    }

    /**
     * Slugify a given string.
     *
     * TODO: Allow overriding this method in case of a different slugify is needed.
     */
    public function slugify(string $toSlug): string
    {
        return Str::slug($toSlug);
    }

    /**
     * When booting the model, we will hook into some events.
     */
    protected static function bootHasSlug(): void
    {
        static::creating(fn (Model $model) => $model->createSlug());
        static::updating(fn (Model $model) => $model->createSlug());
    }
}
