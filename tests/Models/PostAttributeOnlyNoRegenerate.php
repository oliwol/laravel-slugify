<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug', regenerateOnUpdate: false)]
final class PostAttributeOnlyNoRegenerate extends Model
{
    public $timestamps = false;

    protected $table = 'posts_with_slug';

    protected $guarded = [];
}
