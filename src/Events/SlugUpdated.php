<?php

declare(strict_types=1);

namespace Oliwol\Slugify\Events;

use Illuminate\Database\Eloquent\Model;

final readonly class SlugUpdated
{
    public function __construct(
        public Model $model,
        public string $oldSlug,
        public string $newSlug,
    ) {}
}
