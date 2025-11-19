<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;

final class Post extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'posts';

    protected $guarded = [];

    public function getAttributeToCreateSlugFrom(): string
    {
        return 'title';
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
