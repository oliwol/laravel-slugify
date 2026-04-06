<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class SlugHistory extends Model
{
    public $timestamps = false;

    protected $table = 'slug_history';

    protected $guarded = [];

    /**
     * @return MorphTo<Model, $this>
     */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
