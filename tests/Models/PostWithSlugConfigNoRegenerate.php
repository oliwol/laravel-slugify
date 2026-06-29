<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\SlugConfig;

final class PostWithSlugConfigNoRegenerate extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'posts_with_slug';

    protected $guarded = [];

    public function slugConfig(): SlugConfig
    {
        return SlugConfig::create()
            ->from('title')
            ->to('slug')
            ->regenerateOnUpdate(false);
    }
}
