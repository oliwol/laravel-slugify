<?php

declare(strict_types=1);

namespace Tests\Models;

final class UserHasScope extends User
{
    public function getAttributeToCreateSlugFrom(): string
    {
        return 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeSlugQuery($query)
    {
        return $query->where('tenant_id', $this->tenant_id);
    }
}
