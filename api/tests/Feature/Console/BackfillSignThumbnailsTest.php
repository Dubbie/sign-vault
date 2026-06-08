<?php

namespace Tests\Feature\Console;

use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackfillSignThumbnailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_thumbnails_for_signs_missing_one_and_skips_the_rest(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = config('filesystems.default');
        Storage::fake($disk);

        $defaultVariant = $folder->defaultVariant;

        $missingThumbnailKey = "signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/ice-warning-1024x256.png";
        Storage::disk($disk)->put($missingThumbnailKey, $this->makePng(1024, 256));

        $signWithoutThumbnail = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $defaultVariant->id,
            'name' => 'ice-warning',
            'storage_disk' => $disk,
            'storage_key' => $missingThumbnailKey,
            'public_url' => Storage::disk($disk)->url($missingThumbnailKey),
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 1024,
            'height' => 256,
        ]);

        $existingThumbnailKey = "signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/start-banner-512x256.png";
        Storage::disk($disk)->put($existingThumbnailKey, $this->makePng(512, 256));

        $signWithThumbnail = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $defaultVariant->id,
            'name' => 'start-banner',
            'storage_disk' => $disk,
            'storage_key' => $existingThumbnailKey,
            'public_url' => Storage::disk($disk)->url($existingThumbnailKey),
            'thumbnail_url' => 'https://example.test/already-has-a-thumbnail.webp',
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 512,
            'height' => 256,
        ]);

        $this->artisan('signs:backfill-thumbnails')
            ->assertSuccessful();

        $signWithoutThumbnail->refresh();
        $signWithThumbnail->refresh();

        $this->assertNotNull($signWithoutThumbnail->thumbnail_url);
        $this->assertStringEndsWith('.webp', $signWithoutThumbnail->thumbnail_url);

        $thumbnailKey = "signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/ice-warning-1024x256-thumb.webp";
        $this->assertSame(Storage::disk($disk)->url($thumbnailKey), $signWithoutThumbnail->thumbnail_url);
        Storage::disk($disk)->assertExists($thumbnailKey);

        $this->assertSame('https://example.test/already-has-a-thumbnail.webp', $signWithThumbnail->thumbnail_url);
        Storage::disk($disk)->assertMissing(
            "signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/start-banner-512x256-thumb.webp"
        );
    }

    private function makePng(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 32, 64, 96);
        imagefill($image, 0, 0, $background);

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return (string) $contents;
    }
}
