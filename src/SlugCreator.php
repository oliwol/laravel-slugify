<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Oliwol\Slugify\Events\SlugGenerated;
use Oliwol\Slugify\Events\SlugUpdated;
use ReflectionClass;

final readonly class SlugCreator
{
    public function __construct(
        private Model $model,
        private Slugify $config,
    ) {}

    public static function tryForModel(Model $model): ?self
    {
        $attributes = new ReflectionClass($model)->getAttributes(Slugify::class);

        if ($attributes === []) {
            return null;
        }

        return new self($model, $attributes[0]->newInstance());
    }

    public function getTarget(): string
    {
        return $this->config->to ?? $this->model->getRouteKeyName();
    }

    /**
     * @return string|array<int, string>
     */
    public function getSources(): string|array
    {
        return $this->config->from;
    }

    public function isSluggable(): bool
    {
        if ($this->getTarget() === $this->model->getKeyName()) {
            return false;
        }

        $sources = (array) $this->config->from;

        if (count($sources) === 1 && method_exists($this->model, $sources[0])) {
            return filled($this->model->{$sources[0]}());
        }

        foreach ($sources as $field) {
            if (! $this->model->hasAttribute($field)) {
                continue;
            }

            $value = $this->model->getAttribute($field);

            if (filled($value) && is_string($value)) {
                return true;
            }
        }

        return false;
    }

    public function create(): void
    {
        $sources = (array) $this->config->from;
        $target = $this->getTarget();
        $usesMethod = count($sources) === 1 && method_exists($this->model, $sources[0]);

        if (! $usesMethod && ! $this->model->isDirty($sources)) {
            return;
        }

        $currentSlug = $this->model->getAttribute($target);

        if (filled($currentSlug) && $this->model->isDirty($target)) {
            return;
        }

        if (filled($currentSlug) && ! $this->config->regenerateOnUpdate) {
            return;
        }

        if ($usesMethod) {
            // @phpstan-ignore-next-line call.dynamicMethod
            $value = (string) $this->model->{$sources[0]}();
        } else {
            $value = collect($sources)
                ->map(fn (string $field): mixed => $this->model->getAttribute($field))
                ->filter(fn (mixed $v): bool => filled($v) && is_string($v))
                ->implode(' ');
        }

        $oldSlug = $this->model->getOriginal($target);
        $newSlug = $this->generate($value);

        $this->model->setAttribute($target, $newSlug);

        if (filled($oldSlug)) {
            if ($oldSlug !== $newSlug) {
                // @phpstan-ignore-next-line cast.string
                event(new SlugUpdated($this->model, (string) $oldSlug, $newSlug));
            }
        } else {
            event(new SlugGenerated($this->model, $newSlug));
        }
    }

    public function getSourceValue(): ?string
    {
        $sources = (array) $this->config->from;
        $usesMethod = count($sources) === 1 && method_exists($this->model, $sources[0]);

        if ($usesMethod) {
            // @phpstan-ignore-next-line call.dynamicMethod
            $value = (string) $this->model->{$sources[0]}();

            return filled($value) ? $value : null;
        }

        $value = collect($sources)
            ->map(fn (string $field): mixed => $this->model->getAttribute($field))
            ->filter(fn (mixed $v): bool => filled($v) && is_string($v))
            ->implode(' ');

        return filled($value) ? $value : null;
    }

    public function generate(string $value): string
    {
        return $this->incrementIfExists($this->slugify($value));
    }

    private function slugify(string $value): string
    {
        return Str::slug($value, $this->separator(), 'en');
    }

    private function separator(): string
    {
        return $this->config->separator ?? '-';
    }

    private function incrementIfExists(string $slug): string
    {
        $slug = $this->truncate($slug);
        $original = $slug;
        $separator = $this->separator();
        $count = 2;

        while ($this->slugExists($slug)) {
            $suffix = $separator.$count;
            $base = $this->truncate($original, mb_strlen($suffix));
            $slug = $base.$suffix;
            $count++;
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        return $this->model->newQuery()
            ->tap(fn (Builder $query) => $this->applyScopeIfAvailable($query))
            ->where($this->getTarget(), $slug)
            ->whereNot($this->model->getKeyName(), $this->model->getKey())
            ->exists();
    }

    /** @param Builder<Model> $query */
    private function applyScopeIfAvailable(Builder $query): void
    {
        if (method_exists($this->model, 'scopeSlugQuery')) {
            $this->model->scopeSlugQuery($query);
        }
    }

    private function truncate(string $slug, int $reserved = 0): string
    {
        $maxLength = $this->config->maxLength;

        if ($maxLength === null) {
            return $slug;
        }

        $available = $maxLength - $reserved;

        if (mb_strlen($slug) <= $available) {
            return $slug;
        }

        $separator = $this->separator();
        $truncated = mb_substr($slug, 0, $available);
        $nextChar = mb_substr($slug, $available, 1);

        if ($nextChar === $separator) {
            return $truncated;
        }

        $lastSep = mb_strrpos($truncated, $separator);

        if ($lastSep !== false) {
            return mb_substr($truncated, 0, $lastSep);
        }

        return $truncated;
    }
}
