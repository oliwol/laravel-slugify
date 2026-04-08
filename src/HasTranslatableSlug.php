<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Oliwol\Slugify\Events\SlugGenerated;
use Oliwol\Slugify\Events\SlugUpdated;

trait HasTranslatableSlug
{
    use HasSlug {
        HasSlug::createSlug as private createSlugSingle;
        HasSlug::isSluggable as private isSluggableSingle;
    }

    public static function findBySlug(string $slug, ?string $locale = null): ?static
    {
        $instance = new static;
        $target = $instance->getAttributeToSaveSlugTo();
        $locale ??= app()->getLocale();

        return $instance->newQuery()
            ->tap(fn (Builder $query) => $instance->scopeSlugQuery($query))
            ->where("$target->$locale", $slug)
            ->first();
    }

    public function createSlug(): void
    {
        $source = $this->getAttributeToCreateSlugFrom();

        if (! is_string($source)) {
            throw new LogicException(
                'HasTranslatableSlug only supports a single attribute name or method name as source. Use a method source to combine multiple translatable values.'
            );
        }

        $target = $this->getAttributeToSaveSlugTo();
        $usesMethod = method_exists($this, $source);

        $locales = $usesMethod
            ? $this->collectTranslatableLocales()
            : $this->getTranslatedLocales($source);

        foreach ($locales as $locale) {
            $this->createSlugForLocale($source, $target, $locale, $usesMethod);
        }
    }

    public function isSluggable(): bool
    {
        if ($this->getAttributeToSaveSlugTo() === $this->getKeyName()) {
            return false;
        }

        $source = $this->getAttributeToCreateSlugFrom();

        if (! is_string($source)) {
            return $this->isSluggableSingle();
        }

        if (method_exists($this, $source)) {
            return $this->collectTranslatableLocales() !== [];
        }

        $translations = $this->getTranslations($source);

        return array_any($translations, fn ($value): bool => filled($value) && is_string($value));

    }

    protected function slugExistsForLocale(string $slug, string $locale): bool
    {
        $target = $this->getAttributeToSaveSlugTo();

        return $this->newQuery()
            ->tap(fn (Builder $query) => $this->scopeSlugQuery($query))
            ->where("$target->$locale", $slug)
            ->whereNot($this->getKeyName(), $this->getKey())
            ->exists();
    }

    protected function incrementSlugIfExistsForLocale(string $slug, string $locale): string
    {
        $slug = $this->truncateSlug($slug);
        $original = $slug;
        $separator = $this->getSlugSeparator();
        $count = 2;

        while ($this->slugExistsForLocale($slug, $locale)) {
            $suffix = $separator.$count;
            $base = $this->truncateSlug($original, mb_strlen($suffix));
            $slug = $base.$suffix;
            $count++;
        }

        return $slug;
    }

    private function createSlugForLocale(string $source, string $target, string $locale, bool $usesMethod): void
    {
        $originalSlug = $this->getOriginalTranslation($target, $locale);
        $currentSlug = $this->getTranslation($target, $locale, false);

        // Do not override manually set slugs for this locale.
        if (filled($currentSlug) && $currentSlug !== $originalSlug) {
            return;
        }

        // Do not regenerate slug on update when disabled.
        if (filled($originalSlug) && ! $this->shouldRegenerateSlugOnUpdate()) {
            return;
        }

        if ($usesMethod) {
            $previousLocale = $this->getLocale();
            $this->setLocale($locale);
            try {
                $value = $this->{$source}();
            } finally {
                $this->setLocale($previousLocale);
            }
        } else {
            $value = $this->getTranslation($source, $locale, false);
        }

        if (! filled($value) || ! is_string($value)) {
            return;
        }

        // For attribute sources: skip if source value for this locale is unchanged.
        if (! $usesMethod) {
            $originalSourceValue = $this->getOriginalTranslation($source, $locale);

            if ($originalSourceValue === $value && filled($originalSlug)) {
                return;
            }
        }

        $newSlug = $this->incrementSlugIfExistsForLocale(
            slug: $this->slugify($value),
            locale: $locale,
        );

        $this->setTranslation($target, $locale, $newSlug);

        if (filled($originalSlug)) {
            if ($originalSlug !== $newSlug) {
                event(new SlugUpdated($this, $originalSlug, $newSlug));
            }

            return;
        }

        event(new SlugGenerated($this, $newSlug));
    }

    /**
     * @return list<string>
     */
    private function collectTranslatableLocales(): array
    {
        $target = $this->getAttributeToSaveSlugTo();
        $locales = [];

        foreach ($this->getTranslatableAttributes() as $attribute) {
            if ($attribute === $target) {
                continue;
            }

            foreach ($this->getTranslatedLocales($attribute) as $locale) {
                $locales[$locale] = true;
            }
        }

        return array_keys($locales);
    }

    private function getOriginalTranslation(string $key, string $locale): ?string
    {
        $original = $this->getOriginal($key);

        if (! is_array($original)) {
            return null;
        }

        return $original[$locale] ?? null;
    }
}
