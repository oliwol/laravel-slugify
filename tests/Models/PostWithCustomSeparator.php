<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug', separator: '_')]
final class PostWithCustomSeparator extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'posts_with_slug';

    protected $guarded = [];
}
