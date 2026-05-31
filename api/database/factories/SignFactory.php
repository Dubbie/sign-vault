<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sign>
 */
class SignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        $user = User::factory();
        $folder = Folder::factory()->for($user);
        $disk = config('filesystems.default');
        $storageKey = sprintf(
            'signs/%s/%s/%s-%s.png',
            fake()->numberBetween(1, 999),
            Str::slug($name) ?: 'sign',
            Str::slug($name) ?: 'sign',
            Str::lower(Str::random(6))
        );

        return [
            'user_id' => $user,
            'folder_id' => $folder,
            'name' => $name,
            'description' => fake()->optional()->sentence(),
            'storage_disk' => $disk,
            'storage_key' => $storageKey,
            'public_url' => Storage::disk($disk)->url($storageKey),
            'mime_type' => 'image/png',
            'size_bytes' => fake()->numberBetween(1000, 5000000),
            'width' => fake()->numberBetween(16, 2048),
            'height' => fake()->numberBetween(16, 2048),
        ];
    }
}
