<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;

trait HasSlug
{
    /**
     * @return string|array<int, string>
     */
    public function getAttributeToCreateSlugFrom(): string|array
    {
        $attribute = $this->resolveSlugifyAttribute();

        if ($attribute instanceof Slugify) {
            return $attribute->from;
        }

        throw new LogicException(sprintf(
            'Class %s must either override getAttributeToCreateSlugFrom() or use the #[Slugify] attribute.',
            static::class,
        ));
    }

    public function createSlug(): void
    {
        $source = $this->getAttributeToCreateSlugFrom();
        $target = $this->getAttributeToSaveSlugTo();
        $sources = (array) $source;

        // There are no changes to any source attribute; no need to recreate the slug.
        if (! $this->isDirty($sources)) {
            return;
        }

        // Do not override manually set slugs.
        if (filled($this->{$target}) && $this->isDirty($target)) {
            return;
        }

        $value = collect($sources)
            ->map(fn (string $field): ?string => $this->getAttribute($field))
            ->filter(fn (?string $field): bool => filled($field))
            ->implode(' ');

        $this->{$target} = $this->incrementSlugIfExists(
            slug: $this->slugify($value)
        );
    }

    public function getAttributeToSaveSlugTo(): string
    {
        $attribute = $this->resolveSlugifyAttribute();

        if ($attribute instanceof Slugify && $attribute->to !== null) {
            return $attribute->to;
        }

        return $this->getRouteKeyName();
    }

    public function getSlugSeparator(): string
    {
        $attribute = $this->resolveSlugifyAttribute();

        if ($attribute instanceof Slugify && $attribute->separator !== null) {
            return $attribute->separator;
        }

        return '-';
    }

    public function getSlugLanguage(): string
    {
        return 'en';
    }

    public function incrementSlugIfExists(string $slug): string
    {
        $original = $slug;
        $separator = $this->getSlugSeparator();
        $count = 2;

        while ($this->slugExists($slug)) {
            $slug = $original.$separator.$count;
            $count++;
        }

        return $slug;
    }

    public function isSluggable(): bool
    {
        $sources = (array) $this->getAttributeToCreateSlugFrom();

        // Ensure that the route key name is different from the primary key name.
        if ($this->getAttributeToSaveSlugTo() === $this->getKeyName()) {
            return false;
        }

        // Ensure that at least one source attribute exists and has a filled string value.
        $hasFilledSource = false;

        foreach ($sources as $from) {
            if (! $this->hasAttribute($from)) {
                continue;
            }

            $value = $this->getAttribute($from);

            if (filled($value) && is_string($value)) {
                $hasFilledSource = true;

                break;
            }
        }

        return $hasFilledSource;
    }

    public function scopeSlugQuery($query)
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

    private function resolveSlugifyAttribute(): ?Slugify
    {
        /** @var list<ReflectionAttribute<Slugify>> $attributes */
        $attributes = new ReflectionClass($this)->getAttributes(Slugify::class);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }
}
