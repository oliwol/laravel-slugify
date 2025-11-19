<?php

declare(strict_types=1);

namespace Tests\Models;

final class UserHasRouteKeyName extends User
{
    public function getAttributeToCreateSlugFrom(): string
    {
        return 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
