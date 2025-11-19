<?php

declare(strict_types=1);

namespace Tests\Models;

final class UserHasWrongRouteKeyName extends User
{
    public function getAttributeToCreateSlugFrom(): string
    {
        return 'first_name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
