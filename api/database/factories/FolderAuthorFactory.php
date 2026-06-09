<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\FolderAuthor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FolderAuthor>
 */
class FolderAuthorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'folder_id' => Folder::factory(),
            'name' => fake()->name(),
            'source_url' => fake()->optional()->url(),
            'sort_order' => 0,
        ];
    }
}
