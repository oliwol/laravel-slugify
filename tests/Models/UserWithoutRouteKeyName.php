<?php

declare(strict_types=1);

namespace Tests\Models;

final class UserWithoutRouteKeyName extends User
{
    public function getAttributeToCreateSlugFrom(): string
    {
        return 'name';
    }
}
