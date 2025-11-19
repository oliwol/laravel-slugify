<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlug
{
    abstract public function getAttributeToCreateSlugFrom(): string;

    public function createSlug(): void
    {
        if (! $this->isSluggable()) {
            return;
        }

        $createSlugFromAttribute = $this->getAttributeToCreateSlugFrom();
        $saveSlugToAttribute = $this->getAttributeToSaveSlugTo();

        // There are no changes to the source attribute; no need to recreate the slug.
        if (! $this->isDirty($createSlugFromAttribute)) {
            return;
        }

        // Do not override manually set slugs.
        if (filled($this->{$saveSlugToAttribute}) && $this->getOriginal($saveSlugToAttribute) !== $this->{$saveSlugToAttribute}) {
            return;
        }

        $this->{$saveSlugToAttribute} = $this->incrementSlugIfExists(
            slug: $this->slugify($this->{$createSlugFromAttribute})
        );
    }

    public function getAttributeToSaveSlugTo(): string
    {
        return $this->getRouteKeyName();
    }

    public function incrementSlugIfExists(string $slug): string
    {
        $original = $slug;
        $count = 1;

        while (
            $this
                ->newQuery()
                ->tap(fn (Builder $query): Builder => $this->scopeSlugQuery($query))
                ->where($this->getAttributeToSaveSlugTo(), $slug)
                ->whereNot($this->getKeyName(), $this->getKey())
                ->exists()
        ) {
            $slug = $original.($count > 1 ? '-'.$count : '');
            $count++;
        }

        return $slug;
    }

    public function isSluggable(): bool
    {
        $from = $this->getAttributeToCreateSlugFrom();

        // Ensure that the route key name is different from the primary key name.
        if ($this->getAttributeToSaveSlugTo() === $this->getKeyName()) {
            return false;
        }

        // Ensure that the attribute to create slug from exists.
        if (! $this->hasAttribute($from)) {
            return false;
        }

        $value = $this->getAttribute($from);

        // Ensure that the attribute to create slug from is filled.
        if (! filled($value)) {
            return false;
        }

        // Ensure that the attribute to create slug from is a string.
        return is_string($value);
    }

    public function scopeSlugQuery($query)
    {
        return $query;
    }

    public function slugify(string $toSlug): string
    {
        return Str::slug($toSlug);
    }

    protected static function bootHasSlug(): void
    {
        static::saving(fn (Model $model) => $model->createSlug());
    }
}
