<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\Slugify;
use Tests\Factories\PostAttributeOnlyFactory;

#[Slugify(from: 'title', to: 'slug')]
final class PostAttributeOnly extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'posts_with_slug';

    protected $guarded = [];

    protected static function newFactory(): PostAttributeOnlyFactory
    {
        return PostAttributeOnlyFactory::new();
    }
}
