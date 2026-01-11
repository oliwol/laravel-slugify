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
        $source = $this->getAttributeToCreateSlugFrom();
        $target = $this->getAttributeToSaveSlugTo();

        // There are no changes to the source attribute; no need to recreate the slug.
        if (! $this->isDirty($source)) {
            return;
        }

        // Do not override manually set slugs.
        if (filled($this->{$target}) && $this->isDirty($target)) {
            return;
        }

        $this->{$target} = $this->incrementSlugIfExists(
            slug: $this->slugify($this->{$source} ?? '')
        );
    }

    public function getAttributeToSaveSlugTo(): string
    {
        return $this->getRouteKeyName();
    }

    public function getSlugSeparator(): string
    {
        return '-';
    }

    public function getSlugLanguage(): string
    {
        return 'en';
    }

    public function incrementSlugIfExists(string $slug): string
    {
        $original = $slug;
        $count = 2;

        while ($this->slugExists($slug)) {
            $slug = $original.'-'.$count;
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

    public function scopeSlugQuery($query): Builder
    {
        return $query;
    }

    public function slugify(string $toSlug): string
    {
        return Str::slug($toSlug, $this->getSlugSeparator(), $this->getSlugLanguage());
    }

    protected static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            if ($model->isSluggable()) {
                $model->createSlug();
            }
        });
    }

    protected function slugExists(string $slug): bool
    {
        return $this->newQuery()
            ->tap(fn (Builder $query) => $this->scopeSlugQuery($query))
            ->where($this->getAttributeToSaveSlugTo(), $slug)
            ->whereNot($this->getKeyName(), $this->getKey())
            ->exists();
    }
}
