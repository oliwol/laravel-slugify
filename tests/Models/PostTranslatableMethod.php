<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasTranslatableSlug;
use Oliwol\Slugify\Slugify;
use Spatie\Translatable\HasTranslations;

#[Slugify(from: 'getPrefixedTitle', to: 'slug')]
final class PostTranslatableMethod extends Model
{
    use HasTranslatableSlug;
    use HasTranslations;

    public $timestamps = false;

    /** @var array<int, string> */
    public array $translatable = ['title', 'slug'];

    protected $table = 'posts_translatable';

    protected $guarded = [];

    public function getPrefixedTitle(): string
    {
        return 'post-'.$this->getTranslation('title', $this->getLocale(), false);
    }
}
