<?php

declare(strict_types=1);

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Models\UserWithAttribute;

/**
 * @extends Factory<UserWithAttribute>
 */
final class UserWithAttributeFactory extends Factory
{
    protected $model = UserWithAttribute::class;

    public function definition(): array
    {
        return [
            'name' => 'John Doe',
        ];
    }
}
