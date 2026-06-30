<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;
use Oliwol\Slugify\Events\SlugGenerated;
use Oliwol\Slugify\Events\SlugUpdated;
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
     * @return string|array<int, string>|Closure
     */
    public function getAttributeToCreateSlugFrom(): string|array|Closure
    {
        $config = $this->resolveSlugConfig();

        if ($config instanceof SlugConfig) {
            return $config->getFrom();
        }

        throw new LogicException(sprintf(
            'Class %s must either override getAttributeToCreateSlugFrom(), define a slugConfig() method or use the #[Slugify] attribute.',
            static::class,
        ));
    }

    public function createSlug(): void
    {
        $source = $this->getAttributeToCreateSlugFrom();
        $target = $this->getAttributeToSaveSlugTo();
        $usesClosure = $source instanceof Closure;
        $usesMethod = is_string($source) && method_exists($this, $source);

        // When using a closure or method source, skip dirty detection (dependencies are unknown).
        if (! $usesClosure && ! $usesMethod) {
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

        if ($source instanceof Closure) {
            $value = (string) $source($this);
        } elseif (is_string($source) && method_exists($this, $source)) {
            $value = $this->{$source}();
        } else {
            $value = collect((array) $source)
                ->map(fn (string $field): ?string => $this->getAttribute($field))
                ->filter(fn (?string $field): bool => filled($field))
                ->implode(' ');
        }

        $oldSlug = $this->getOriginal($target);
        $newSlug = $this->incrementSlugIfExists(
            slug: $this->slugify($value)
        );

        $this->{$target} = $newSlug;

        if (filled($oldSlug)) {
            if ($oldSlug !== $newSlug) {
                event(new SlugUpdated($this, $oldSlug, $newSlug));
            }
        } else {
            event(new SlugGenerated($this, $newSlug));
        }
    }

    public function getAttributeToSaveSlugTo(): string
    {
        $config = $this->resolveSlugConfig();

        if ($config instanceof SlugConfig && $config->getTo() !== null) {
            return $config->getTo();
        }

        return $this->getRouteKeyName();
    }

    public function getSlugSeparator(): string
    {
        $config = $this->resolveSlugConfig();

        if ($config instanceof SlugConfig && $config->getSeparator() !== null) {
            return $config->getSeparator();
        }

        return '-';
    }

    public function getMaxSlugLength(): ?int
    {
        $config = $this->resolveSlugConfig();

        if ($config instanceof SlugConfig && $config->getMaxLength() !== null) {
            return $config->getMaxLength();
        }

        return null;
    }

    public function shouldRegenerateSlugOnUpdate(): bool
    {
        $config = $this->resolveSlugConfig();

        if ($config instanceof SlugConfig) {
            return $config->shouldRegenerateOnUpdate();
        }

        return true;
    }

    public function getSlugLanguage(): string
    {
        return 'en';
    }

    public function shouldUseSlugForRouteBinding(): bool
    {
        $config = $this->resolveSlugConfig();

        return $config instanceof SlugConfig
            && $config->usesRouteBinding()
            && $config->getTo() !== null;
    }

    public function getRouteKeyName(): string
    {
        $config = $this->resolveSlugConfig();

        if ($config instanceof SlugConfig && $config->usesRouteBinding() && $config->getTo() !== null) {
            return $config->getTo();
        }

        return $this->getKeyName();
    }

    public function isIdAnchored(): bool
    {
        $config = $this->resolveSlugConfig();

        return $config instanceof SlugConfig && $config->usesIdAnchoring();
    }

    public function getRouteKey()
    {
        if (! $this->isIdAnchored()) {
            return $this->getAttribute($this->getRouteKeyName());
        }

        $slug = $this->getAttribute($this->getAttributeToSaveSlugTo());

        return (is_string($slug) ? $slug : '').'-'.$this->getKey();
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($this->isIdAnchored() && $field === null) {
            return $query->where($this->getKeyName(), $this->extractIdFromRouteKey($value));
        }

        return $query->where($field ?? $this->getRouteKeyName(), $value);
    }

    public function incrementSlugIfExists(string $slug): string
    {
        // With ID-anchored slugs the primary key already makes every URL unique,
        // so the slug itself stays clean — no numeric increment is appended.
        if ($this->isIdAnchored()) {
            return $this->truncateSlug($slug);
        }

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

        // When source resolves to a closure, delegate to the closure result.
        if ($source instanceof Closure) {
            return filled($source($this));
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

    private function extractIdFromRouteKey(mixed $routeKey): ?string
    {
        if (! is_string($routeKey)) {
            return null;
        }

        // The trailing number is always the primary key (appendId builds "{slug}-{id}").
        if (preg_match('/(\d+)$/', $routeKey, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function resolveSlugConfig(): ?SlugConfig
    {
        if (method_exists($this, 'slugConfig')) {
            /** @var SlugConfig */
            return $this->slugConfig();
        }

        $attribute = $this->resolveSlugifyAttribute();

        return $attribute instanceof Slugify
            ? SlugConfig::fromAttribute($attribute)
            : null;
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
