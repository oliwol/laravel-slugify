<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;

final class AuthorWithMethod extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'users';

    protected $guarded = [];

    /**
     * @return array<int, string>
     */
    public function getAttributeToCreateSlugFrom(): array
    {
        return ['first_name', 'last_name'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
