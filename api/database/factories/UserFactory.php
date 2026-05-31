<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discord_id' => fake()->unique()->numerify('##################'),
            'discord_username' => fake()->unique()->userName(),
            'discord_global_name' => fake()->optional()->name(),
            'discord_avatar' => sprintf(
                'https://cdn.discordapp.com/avatars/%s/%s.png',
                fake()->numerify('##################'),
                fake()->sha1()
            ),
            'email' => fake()->optional()->safeEmail(),
        ];
    }
}
