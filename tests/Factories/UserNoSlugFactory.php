<?php

declare(strict_types=1);

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Models\UserNoSlug;

/**
 * @extends Factory<UserNoSlug>
 */
final class UserNoSlugFactory extends Factory
{
    protected $model = UserNoSlug::class;

    public function definition(): array
    {
        return [
            'name' => 'John Doe',
        ];
    }
}
