<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Slugify
{
    /**
     * @param  string|array<int, string>  $from
     */
    public function __construct(
        public string|array $from,
        public ?string $to = null,
        public ?string $separator = null,
        public ?int $maxLength = null,
        public bool $regenerateOnUpdate = true,
        public bool $routeBinding = false,
    ) {}
}
