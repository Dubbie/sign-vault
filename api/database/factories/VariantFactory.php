<?php

namespace Database\Factories;

use App\Models\Folder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Variant>
 */
class VariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'folder_id' => Folder::factory(),
            'name' => 'Default',
            'is_default' => true,
            'sort_order' => 0,
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'is_default' => false,
        ]);
    }
}
