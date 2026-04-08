<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasTranslatableSlug;
use Oliwol\Slugify\Slugify;
use Spatie\Translatable\HasTranslations;

#[Slugify(from: 'title', to: 'slug', regenerateOnUpdate: false)]
final class PostTranslatableNoRegenerate extends Model
{
    use HasTranslatableSlug;
    use HasTranslations;

    public $timestamps = false;

    /** @var array<int, string> */
    public array $translatable = ['title', 'slug'];

    protected $table = 'posts_translatable';

    protected $guarded = [];
}
