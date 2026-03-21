<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug', maxLength: 15)]
final class PostWithMaxLength extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'posts_with_slug';

    protected $guarded = [];
}
