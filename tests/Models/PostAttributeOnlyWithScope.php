<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
final class PostAttributeOnlyWithScope extends Model
{
    public $timestamps = false;

    protected $table = 'posts_with_slug';

    protected $guarded = [];

    public function scopeSlugQuery(Builder $query): void {}
}
