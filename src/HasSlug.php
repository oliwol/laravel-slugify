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
    public static function findBySlug(string $slug): ?static
    {
        $instance = new static;

        return $instance->newQuery()
            ->tap(fn (Builder $query) => $instance->scopeSlugQuery($query))
            ->where($instance->getAttributeToSaveSlugTo(), $slug)
            ->first();
    }

    public static function findBySlugOrFail(string $slug): static
    {
        $instance = new static;

        return $instance->newQuery()
            ->tap(fn (Builder $query) => $instance->scopeSlugQuery($query))
            ->where($instance->getAttributeToSaveSlugTo(), $slug)
            ->firstOrFail();
    }

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
        $usesMethod = is_string($source) && method_exists($this, $source);

        // When using a method source, skip dirty detection (dependencies are unknown).
        if (! $usesMethod) {
            $sources = (array) $source;

            // There are no changes to any source attribute; no need to recreate the slug.
            if (! $this->isDirty($sources)) {
                return;
            }
        }

        // Do not override manually set slugs.
        if (filled($this->{$target}) && $this->isDirty($target)) {
            return;
        }

        // Do not regenerate slug on update when disabled.
        if (filled($this->{$target}) && ! $this->shouldRegenerateSlugOnUpdate()) {
            return;
        }

        if ($usesMethod) {
            $value = $this->{$source}();
        } else {
            $sources = (array) $source;
            $value = collect($sources)
                ->map(fn (string $field): ?string => $this->getAttribute($field))
                ->filter(fn (?string $field): bool => filled($field))
                ->implode(' ');
        }

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

    public function getMaxSlugLength(): ?int
    {
        $attribute = $this->resolveSlugifyAttribute();

        if ($attribute instanceof Slugify && $attribute->maxLength !== null) {
            return $attribute->maxLength;
        }

        return null;
    }

    public function shouldRegenerateSlugOnUpdate(): bool
    {
        $attribute = $this->resolveSlugifyAttribute();

        if ($attribute instanceof Slugify) {
            return $attribute->regenerateOnUpdate;
        }

        return true;
    }

    public function getSlugLanguage(): string
    {
        return 'en';
    }

    public function incrementSlugIfExists(string $slug): string
    {
        $slug = $this->truncateSlug($slug);
        $original = $slug;
        $separator = $this->getSlugSeparator();
        $count = 2;

        while ($this->slugExists($slug)) {
            $suffix = $separator.$count;
            $base = $this->truncateSlug($original, mb_strlen($suffix));
            $slug = $base.$suffix;
            $count++;
        }

        return $slug;
    }

    public function isSluggable(): bool
    {
        $source = $this->getAttributeToCreateSlugFrom();

        // Ensure that the route key name is different from the primary key name.
        if ($this->getAttributeToSaveSlugTo() === $this->getKeyName()) {
            return false;
        }

        // When source resolves to a method, delegate to the method result.
        if (is_string($source) && method_exists($this, $source)) {
            return filled($this->{$source}());
        }

        // Ensure that at least one source attribute exists and has a filled string value.
        $sources = (array) $source;
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

    public function truncateSlug(string $slug, int $reservedLength = 0): string
    {
        $maxLength = $this->getMaxSlugLength();

        if ($maxLength === null) {
            return $slug;
        }

        $available = $maxLength - $reservedLength;

        if (mb_strlen($slug) <= $available) {
            return $slug;
        }

        $separator = $this->getSlugSeparator();
        $truncated = mb_substr($slug, 0, $available);

        // If the cut lands exactly on a word boundary, no further trimming needed.
        $nextChar = mb_substr($slug, $available, 1);

        if ($nextChar === $separator) {
            return $truncated;
        }

        // Trim at the last separator to avoid cutting mid-word.
        $lastSeparator = mb_strrpos($truncated, (string) $separator);

        if ($lastSeparator !== false) {
            return mb_substr($truncated, 0, $lastSeparator);
        }

        return $truncated;
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
