<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Slugify
{
    public function __construct(
        public string $from,
        public ?string $to = null,
    ) {}
}
