<?php

namespace Tests\Feature\Console;

use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackfillThumbnailStorageKeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_backfills_thumbnail_storage_keys_for_existing_signs_without_inserting_new_rows(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Warning Signs',
            'slug' => 'warning-signs',
        ]);

        $disk = config('filesystems.default');
        $diskBaseUrl = 'https://cdn.example.test';
        config()->set("filesystems.disks.{$disk}.url", $diskBaseUrl);
        Storage::fake($disk);

        $defaultVariant = $folder->defaultVariant;
        $thumbnailKey = "signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/yield-thumb.webp";

        $sign = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $defaultVariant->id,
            'name' => 'yield',
            'storage_disk' => $disk,
            'storage_key' => "signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/yield.png",
            'public_url' => $diskBaseUrl."/signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/yield.png",
            'thumbnail_url' => $diskBaseUrl."/{$thumbnailKey}",
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 512,
            'height' => 512,
        ]);

        $this->artisan('signs:backfill-thumbnail-storage-keys')
            ->assertSuccessful();

        $sign->refresh();

        $this->assertSame($thumbnailKey, $sign->thumbnail_storage_key);
        $this->assertDatabaseCount('signs', 1);
    }
}
