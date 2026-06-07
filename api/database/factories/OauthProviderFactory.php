<?php

namespace Database\Factories;

use App\Models\OauthProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OauthProvider>
 */
class OauthProviderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => OauthProvider::DISCORD,
            'provider_user_id' => fake()->unique()->numerify('##################'),
            'username' => fake()->userName(),
            'display_name' => fake()->optional()->name(),
            'avatar_url' => null,
            'email' => fake()->optional()->safeEmail(),
        ];
    }

    public function discord(): static
    {
        return $this->state(['provider' => OauthProvider::DISCORD]);
    }

    public function trackmania(): static
    {
        return $this->state([
            'provider' => OauthProvider::TRACKMANIA,
            'provider_user_id' => fake()->uuid(),
        ]);
    }
}
