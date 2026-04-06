<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\HasSlugHistory;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
final class PostWithSlugHistory extends Model
{
    use HasSlug, HasSlugHistory;

    public $timestamps = false;

    protected $table = 'posts_with_slug';

    protected $guarded = [];
}
