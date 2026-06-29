<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\SlugConfig;

final class UserWithSlugConfigClosure extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'users';

    protected $guarded = [];

    public function slugConfig(): SlugConfig
    {
        return SlugConfig::create()
            ->from(fn (self $model): string => $model->first_name.' '.$model->last_name)
            ->to('slug');
    }
}
