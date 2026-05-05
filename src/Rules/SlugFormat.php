<?php

declare(strict_types=1);

namespace Oliwol\Slugify\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class SlugFormat implements ValidationRule
{
    public function __construct(private string $separator = '-') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail($this->message());

            return;
        }

        $pattern = '/^[a-z0-9]+('.preg_quote($this->separator, '/').'[a-z0-9]+)*$/';

        if (! preg_match($pattern, $value)) {
            $fail($this->message());
        }
    }

    private function message(): string
    {
        $message = trans('slugify::validation.slug_format');

        return is_string($message) ? $message : 'The :attribute must be a valid slug.';
    }
}
