<?php

declare(strict_types=1);

namespace Oliwol\Slugify\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SlugRule implements ValidationRule
{
    private ?Model $ignoredModel = null;

    /** @var array<string, mixed> */
    private array $scopes = [];

    /** @param class-string<Model> $modelClass */
    public function __construct(private readonly string $modelClass) {}

    /** @param class-string<Model> $modelClass */
    public static function for(string $modelClass): static
    {
        return new self($modelClass);
    }

    public function ignore(Model $model): static
    {
        $this->ignoredModel = $model;

        return $this;
    }

    public function scope(string $column, mixed $value): static
    {
        $this->scopes[$column] = $value;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $instance = new $this->modelClass;

        foreach ($this->scopes as $column => $scopeValue) {
            $instance->setAttribute($column, $scopeValue);
        }

        /** @var string $slugColumn */
        $slugColumn = $instance->getAttributeToSaveSlugTo(); // @phpstan-ignore method.notFound

        $query = $instance->newQuery()
            ->tap(fn (Builder $q) => $instance->scopeSlugQuery($q)) // @phpstan-ignore method.notFound
            ->where($slugColumn, $value);

        foreach ($this->scopes as $column => $scopeValue) {
            $query->where($column, $scopeValue);
        }

        if ($this->ignoredModel instanceof Model) {
            $query->whereNot($instance->getKeyName(), $this->ignoredModel->getKey());
        }

        if ($query->exists()) {
            $message = trans('slugify::validation.slug_unique');
            $fail(is_string($message) ? $message : 'The :attribute has already been taken.');
        }
    }
}
