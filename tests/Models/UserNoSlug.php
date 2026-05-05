<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

final class UserNoSlug extends Model
{
    public $timestamps = false;

    protected $table = 'users';

    protected $guarded = [];
}
