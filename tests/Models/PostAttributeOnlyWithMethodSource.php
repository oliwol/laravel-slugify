<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'slugSource', to: 'slug')]
final class PostAttributeOnlyWithMethodSource extends Model
{
    public $timestamps = false;

    protected $table = 'posts_with_slug';

    protected $guarded = [];

    public function slugSource(): string
    {
        return (string) ($this->title ?? '');
    }
}
