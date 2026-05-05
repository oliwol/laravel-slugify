<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'name', to: 'slug', routeBinding: true)]
final class UserWithRouteBinding extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'users';

    protected $guarded = [];
}
