<?php

namespace Database\Factories;

use App\Enums\FolderVisibility;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name) ?: 'folder',
            'public_slug' => Folder::generatePublicSlugFor($name),
            'visibility' => FolderVisibility::Private,
            'password_hash' => null,
        ];
    }
}
