<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;

final class UserHasWrongRouteKeyName extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'users';

    protected $guarded = [];

    public function getAttributeToCreateSlugFrom(): string
    {
        return 'first_name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
