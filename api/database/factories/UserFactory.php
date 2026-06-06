<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'display_name' => fake()->name(),
            'avatar_url'   => null,
            'email'        => fake()->optional()->safeEmail(),
        ];
    }
}
