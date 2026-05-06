<?php

declare(strict_types=1);

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Models\PostAttributeOnly;

/**
 * @extends Factory<PostAttributeOnly>
 */
final class PostAttributeOnlyFactory extends Factory
{
    protected $model = PostAttributeOnly::class;

    public function definition(): array
    {
        return [
            'title' => 'Hello World',
        ];
    }
}
