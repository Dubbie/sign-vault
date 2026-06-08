<?php

namespace Tests\Feature\Sign;

use App\Models\ActivityLog;
use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SignManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_without_session_id_creates_a_single_activity_log_row(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $this->fakeSignStorage();
        Sanctum::actingAs($user);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [
                UploadedFile::fake()->image('ice-warning.png', 1024, 256),
                UploadedFile::fake()->image('start-banner.png', 512, 256),
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'event' => ActivityLog::SIGNS_UPLOADED,
            'actor_id' => $user->id,
            'subject_folder_id' => $folder->id,
            'upload_session_id' => null,
        ]);

        $activityLog = ActivityLog::query()
            ->where('event', ActivityLog::SIGNS_UPLOADED)
            ->sole();

        $this->assertSame(2, $activityLog->metadata['count'] ?? null);
        $this->assertSame($folder->name, $activityLog->metadata['folder_name'] ?? null);
    }

    public function test_uploads_with_the_same_session_id_are_aggregated_into_one_activity_log_row(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $this->fakeSignStorage();
        Sanctum::actingAs($user);

        $uploadSessionId = '31f2bbf7-11d3-486f-a247-572822ed4620';

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'upload_session_id' => $uploadSessionId,
            'files' => [
                UploadedFile::fake()->image('ice-warning.png', 1024, 256),
                UploadedFile::fake()->image('start-banner.png', 512, 256),
            ],
        ])->assertCreated();

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'upload_session_id' => $uploadSessionId,
            'files' => [
                UploadedFile::fake()->image('finish-banner.png', 600, 150),
            ],
        ])->assertCreated();

        $activityLog = ActivityLog::query()
            ->where('event', ActivityLog::SIGNS_UPLOADED)
            ->sole();

        $this->assertSame($uploadSessionId, $activityLog->upload_session_id);
        $this->assertSame(3, $activityLog->metadata['count'] ?? null);
    }

    public function test_failed_upload_attempt_does_not_create_a_new_log_row_for_an_existing_session(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $this->fakeSignStorage();
        Sanctum::actingAs($user);

        $uploadSessionId = '66e4a67d-4b53-4601-ac54-9342be060f9f';

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'upload_session_id' => $uploadSessionId,
            'files' => [
                UploadedFile::fake()->image('ice-warning.png', 1024, 256),
                UploadedFile::fake()->image('start-banner.png', 512, 256),
            ],
        ])->assertCreated();

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'upload_session_id' => $uploadSessionId,
            'files' => [
                UploadedFile::fake()->create('broken.txt', 10, 'text/plain'),
            ],
        ])->assertStatus(422);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'upload_session_id' => $uploadSessionId,
            'files' => [
                UploadedFile::fake()->image('retry-banner.png', 256, 256),
            ],
        ])->assertCreated();

        $activityLog = ActivityLog::query()
            ->where('event', ActivityLog::SIGNS_UPLOADED)
            ->sole();

        $this->assertSame(3, $activityLog->metadata['count'] ?? null);
    }

    public function test_authenticated_user_can_upload_a_valid_sign_to_own_folder(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = $this->fakeSignStorage();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [
                UploadedFile::fake()->image('ice-warning.png', 1024, 256),
                UploadedFile::fake()->image('start-banner.png', 512, 256),
            ],
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'signs' => [
                    '*' => [
                        'id',
                        'folder_id',
                        'name',
                        'public_url',
                        'mime_type',
                        'size_bytes',
                        'width',
                        'height',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'signs')
            ->assertJsonPath('signs.0.folder_id', $folder->id)
            ->assertJsonPath('signs.0.name', 'ice-warning')
            ->assertJsonPath('signs.0.mime_type', 'image/png')
            ->assertJsonPath('signs.0.width', 1024)
            ->assertJsonPath('signs.0.height', 256)
            ->assertJsonPath('signs.1.name', 'start-banner')
            ->assertJsonPath('signs.1.mime_type', 'image/png')
            ->assertJsonPath('signs.1.width', 512)
            ->assertJsonPath('signs.1.height', 256)
            ->assertJsonMissingPath('signs.0.storage_key')
            ->assertJsonMissingPath('signs.0.storage_disk')
            ->assertJsonMissingPath('signs.1.storage_key')
            ->assertJsonMissingPath('signs.1.storage_disk');

        $this->assertDatabaseCount('signs', 2);

        $firstSign = Sign::query()->where('name', 'ice-warning')->firstOrFail();
        $secondSign = Sign::query()->where('name', 'start-banner')->firstOrFail();

        $this->assertSame($user->id, $firstSign->user_id);
        $this->assertSame($folder->id, $firstSign->folder_id);
        $this->assertSame('image/png', $firstSign->mime_type);
        $this->assertSame(1024, $firstSign->width);
        $this->assertSame(256, $firstSign->height);
        $this->assertSame($disk, $firstSign->storage_disk);
        $this->assertSame(Storage::disk($disk)->url($firstSign->storage_key), $firstSign->public_url);
        Storage::disk($disk)->assertExists($firstSign->storage_key);
        $this->assertSame('public', Storage::disk($disk)->getVisibility($firstSign->storage_key));

        $this->assertSame($user->id, $secondSign->user_id);
        $this->assertSame($folder->id, $secondSign->folder_id);
        $this->assertSame('image/png', $secondSign->mime_type);
        $this->assertSame(512, $secondSign->width);
        $this->assertSame(256, $secondSign->height);
        $this->assertSame($disk, $secondSign->storage_disk);
        $this->assertSame(Storage::disk($disk)->url($secondSign->storage_key), $secondSign->public_url);
        Storage::disk($disk)->assertExists($secondSign->storage_key);
        $this->assertSame('public', Storage::disk($disk)->getVisibility($secondSign->storage_key));
    }

    public function test_authenticated_user_can_list_signs_in_own_folder(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = $this->fakeSignStorage();
        $defaultVariant = $folder->defaultVariant;
        $storageKey = 'signs/'.$user->id.'/'.$folder->id.'/'.$defaultVariant->id.'/ice-warning.png';

        Storage::disk($disk)->put($storageKey, 'fake-image');

        $sign = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $defaultVariant->id,
            'name' => 'ice-warning',
            'storage_disk' => $disk,
            'storage_key' => $storageKey,
            'public_url' => Storage::disk($disk)->url($storageKey),
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 1024,
            'height' => 256,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/folders/{$folder->id}/signs")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sign->id)
            ->assertJsonPath('data.0.folder_id', $folder->id)
            ->assertJsonPath('data.0.public_url', Storage::disk($disk)->url($storageKey))
            ->assertJsonMissingPath('data.0.storage_key')
            ->assertJsonMissingPath('data.0.storage_disk');
    }

    public function test_authenticated_user_can_view_own_sign(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = $this->fakeSignStorage();
        $defaultVariant = $folder->defaultVariant;
        $storageKey = 'signs/'.$user->id.'/'.$folder->id.'/'.$defaultVariant->id.'/ice-warning.png';

        Storage::disk($disk)->put($storageKey, 'fake-image');

        $sign = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $defaultVariant->id,
            'name' => 'ice-warning',
            'storage_disk' => $disk,
            'storage_key' => $storageKey,
            'public_url' => Storage::disk($disk)->url($storageKey),
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 1024,
            'height' => 256,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/signs/{$sign->id}")
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'folder_id',
                'name',
                'public_url',
                'mime_type',
                'size_bytes',
                'width',
                'height',
                'created_at',
                'updated_at',
            ])
            ->assertJsonPath('id', $sign->id)
            ->assertJsonPath('folder_id', $folder->id)
            ->assertJsonPath('name', 'ice-warning')
            ->assertJsonPath('public_url', Storage::disk($disk)->url($storageKey))
            ->assertJsonMissingPath('storage_key')
            ->assertJsonMissingPath('storage_disk');
    }

    public function test_authenticated_user_can_delete_own_sign(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = $this->fakeSignStorage();
        $defaultVariant = $folder->defaultVariant;
        $storageKey = 'signs/'.$user->id.'/'.$folder->id.'/'.$defaultVariant->id.'/ice-warning.png';

        Storage::disk($disk)->put($storageKey, 'fake-image');

        $sign = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $defaultVariant->id,
            'name' => 'ice-warning',
            'storage_disk' => $disk,
            'storage_key' => $storageKey,
            'public_url' => Storage::disk($disk)->url($storageKey),
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 1024,
            'height' => 256,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/signs', [
            'ids' => [$sign->id],
        ])->assertNoContent();

        $this->assertDatabaseMissing('signs', [
            'id' => $sign->id,
        ]);
    }

    public function test_deleting_a_sign_succeeds_even_if_storage_object_is_missing(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = $this->fakeSignStorage();
        $defaultVariant = $folder->defaultVariant;
        $storageKey = 'signs/'.$user->id.'/'.$folder->id.'/'.$defaultVariant->id.'/ice-warning.png';

        $sign = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $defaultVariant->id,
            'name' => 'ice-warning',
            'storage_disk' => $disk,
            'storage_key' => $storageKey,
            'public_url' => Storage::disk($disk)->url($storageKey),
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 1024,
            'height' => 256,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/signs', [
            'ids' => [$sign->id],
        ])->assertNoContent();

        $this->assertDatabaseMissing('signs', [
            'id' => $sign->id,
        ]);
    }

    public function test_user_cannot_list_signs_from_another_users_folder(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $folder = Folder::factory()->for($otherUser)->create([
            'name' => 'Private Folder',
            'slug' => 'private-folder',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/folders/{$folder->id}/signs")
            ->assertForbidden();
    }

    public function test_user_cannot_upload_signs_to_another_users_folder(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $folder = Folder::factory()->for($otherUser)->create([
            'name' => 'Private Folder',
            'slug' => 'private-folder',
        ]);

        $this->fakeSignStorage();
        Sanctum::actingAs($user);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'file' => UploadedFile::fake()->image('ice-warning.png', 1024, 256),
            'name' => 'Ice Warning',
        ])
            ->assertForbidden();
    }

    public function test_user_cannot_view_another_users_sign(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $folder = Folder::factory()->for($otherUser)->create([
            'name' => 'Private Folder',
            'slug' => 'private-folder',
        ]);

        $sign = Sign::create([
            'user_id' => $otherUser->id,
            'folder_id' => $folder->id,
            'variant_id' => $folder->defaultVariant->id,
            'name' => 'ice-warning',
            'storage_disk' => 's3',
            'storage_key' => 'signs/2/private-folder/'.$folder->defaultVariant->id.'/ice-warning.png',
            'public_url' => 'http://example.test/signs/2/private-folder/'.$folder->defaultVariant->id.'/ice-warning.png',
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 1024,
            'height' => 256,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/signs/{$sign->id}")
            ->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_sign(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $folder = Folder::factory()->for($otherUser)->create([
            'name' => 'Private Folder',
            'slug' => 'private-folder',
        ]);

        $sign = Sign::create([
            'user_id' => $otherUser->id,
            'folder_id' => $folder->id,
            'variant_id' => $folder->defaultVariant->id,
            'name' => 'ice-warning',
            'storage_disk' => 's3',
            'storage_key' => 'signs/2/private-folder/'.$folder->defaultVariant->id.'/ice-warning.png',
            'public_url' => 'http://example.test/signs/2/private-folder/'.$folder->defaultVariant->id.'/ice-warning.png',
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 1024,
            'height' => 256,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/signs', [
            'ids' => [$sign->id],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');
    }

    public function test_invalid_file_types_are_rejected(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $this->fakeSignStorage();
        Sanctum::actingAs($user);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [
                UploadedFile::fake()->create('document.txt', 10, 'text/plain'),
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('files.0');
    }

    public function test_avif_files_are_accepted(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        Sanctum::actingAs($user);

        $file = $this->makeAvifUpload('avif-sign.avif');

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [$file],
        ])->assertCreated();

        $this->assertDatabaseHas('signs', [
            'folder_id' => $folder->id,
            'name' => 'avif-sign',
            'mime_type' => 'image/avif',
        ]);
    }

    public function test_webm_files_are_accepted(): void
    {
        $disk = $this->fakeSignStorage();

        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Animated Signs',
            'slug' => 'animated-signs',
        ]);

        Sanctum::actingAs($user);

        $file = $this->makeWebmUpload('wave-banner.webm', 320, 160);

        $response = $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [$file],
        ]);

        $response->assertCreated()
            ->assertJsonPath('signs.0.name', 'wave-banner')
            ->assertJsonPath('signs.0.mime_type', 'video/webm')
            ->assertJsonPath('signs.0.width', 320)
            ->assertJsonPath('signs.0.height', 160);

        $sign = Sign::query()->where('name', 'wave-banner')->firstOrFail();

        $this->assertSame('video/webm', $sign->mime_type);
        $this->assertSame(320, $sign->width);
        $this->assertSame(160, $sign->height);
        $this->assertSame($disk, $sign->storage_disk);
        Storage::disk($disk)->assertExists($sign->storage_key);
    }

    private function makeAvifUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'avif_');

        if ($path === false) {
            $this->fail('Unable to create a temporary AVIF file.');
        }

        $image = imagecreatetruecolor(2, 2);
        $background = imagecolorallocate($image, 32, 64, 96);
        imagefill($image, 0, 0, $background);

        if (! imageavif($image, $path)) {
            imagedestroy($image);
            @unlink($path);
            $this->fail('Unable to encode a temporary AVIF file.');
        }

        imagedestroy($image);

        return new UploadedFile($path, $name, 'image/avif', null, true);
    }

    private function makeWebmUpload(string $name, int $width, int $height): UploadedFile
    {
        if (! $this->commandExists('ffmpeg')) {
            $this->markTestSkipped('ffmpeg is required to generate a WebM fixture for this test.');
        }

        $path = tempnam(sys_get_temp_dir(), 'webm_');

        if ($path === false) {
            $this->fail('Unable to create a temporary WebM file.');
        }

        $finalPath = $path.'.webm';

        if (! @rename($path, $finalPath)) {
            @unlink($path);
            $this->fail('Unable to prepare a temporary WebM file.');
        }

        $process = new Process([
            'ffmpeg',
            '-f',
            'lavfi',
            '-i',
            sprintf('color=c=black:s=%dx%d:d=1', $width, $height),
            '-c:v',
            'libvpx-vp9',
            '-an',
            '-y',
            $finalPath,
        ]);

        $process->run();

        if (! $process->isSuccessful()) {
            @unlink($finalPath);
            $this->fail('Unable to generate a temporary WebM file with ffmpeg.');
        }

        return new UploadedFile($finalPath, $name, 'video/webm', null, true);
    }

    private function commandExists(string $command): bool
    {
        $process = new Process(['which', $command]);
        $process->run();

        return $process->isSuccessful();
    }

    public function test_missing_file_is_rejected(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $this->fakeSignStorage();
        Sanctum::actingAs($user);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('files');
    }

    public function test_too_many_files_are_rejected(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $this->fakeSignStorage();
        Sanctum::actingAs($user);
        config()->set('signs.max_upload_files', 20);

        $files = array_map(
            fn (int $index) => UploadedFile::fake()->image("sign-{$index}.png", 1024, 256),
            range(1, 21)
        );

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => $files,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('files');
    }

    public function test_sign_name_can_be_derived_from_filename(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $this->fakeSignStorage();
        Sanctum::actingAs($user);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [
                UploadedFile::fake()->image('start-banner.png', 1024, 256),
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('signs.0.name', 'start-banner');

        $this->assertDatabaseHas('signs', [
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'name' => 'start-banner',
        ]);
    }

    public function test_uploading_the_same_named_image_with_different_dimensions_creates_a_new_sign(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = $this->fakeSignStorage();

        Sanctum::actingAs($user);

        $firstResponse = $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [
                UploadedFile::fake()->image('ice-warning.png', 1024, 256),
            ],
        ]);

        $firstResponse->assertCreated();

        $firstSignId = $firstResponse->json('signs.0.id');
        $defaultVariant = $folder->defaultVariant;
        $storageKey = "signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/ice-warning-1024x256.png";
        $firstSign = Sign::query()->findOrFail($firstSignId);

        $this->assertSame($storageKey, $firstSign->storage_key);
        $this->assertSame(Storage::disk($disk)->url($storageKey), $firstSign->public_url);
        Storage::disk($disk)->assertExists($storageKey);
        $this->assertSame(1024, $firstSign->width);
        $this->assertSame(256, $firstSign->height);

        $secondResponse = $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [
                UploadedFile::fake()->image('ice-warning.png', 512, 128),
            ],
        ]);

        $secondResponse->assertCreated()
            ->assertJsonPath('signs.0.id', fn ($id) => $id !== $firstSignId)
            ->assertJsonPath('signs.0.width', 512)
            ->assertJsonPath('signs.0.height', 128);

        $this->assertDatabaseCount('signs', 2);

        $updatedSign = Sign::query()->findOrFail($firstSignId);
        $newSign = Sign::query()
            ->where('name', 'ice-warning')
            ->where('width', 512)
            ->where('height', 128)
            ->firstOrFail();

        $this->assertSame($storageKey, $updatedSign->storage_key);
        $this->assertSame(Storage::disk($disk)->url($storageKey), $updatedSign->public_url);
        $this->assertSame(1024, $updatedSign->width);
        $this->assertSame(256, $updatedSign->height);
        Storage::disk($disk)->assertExists($storageKey);

        $newStorageKey = "signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/ice-warning-512x128.png";
        $this->assertSame($newStorageKey, $newSign->storage_key);
        $this->assertSame(Storage::disk($disk)->url($newStorageKey), $newSign->public_url);
        Storage::disk($disk)->assertExists($newStorageKey);
    }

    public function test_uploading_the_same_named_image_with_the_same_dimensions_replaces_the_existing_sign(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = $this->fakeSignStorage();

        Sanctum::actingAs($user);

        $firstResponse = $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [
                UploadedFile::fake()->image('ice-warning.png', 1024, 256),
            ],
        ]);

        $firstResponse->assertCreated();

        $firstSignId = $firstResponse->json('signs.0.id');
        $defaultVariant = $folder->defaultVariant;
        $storageKey = "signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/ice-warning-1024x256.png";

        $secondResponse = $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [
                UploadedFile::fake()->image('ice-warning.png', 1024, 256),
            ],
        ]);

        $secondResponse->assertCreated()
            ->assertJsonPath('signs.0.id', $firstSignId)
            ->assertJsonPath('signs.0.public_url', Storage::disk($disk)->url($storageKey))
            ->assertJsonPath('signs.0.width', 1024)
            ->assertJsonPath('signs.0.height', 256);

        $this->assertDatabaseCount('signs', 1);

        $updatedSign = Sign::query()->findOrFail($firstSignId);
        $this->assertSame($storageKey, $updatedSign->storage_key);
        $this->assertSame(Storage::disk($disk)->url($storageKey), $updatedSign->public_url);
        Storage::disk($disk)->assertExists($storageKey);
    }

    public function test_storage_metadata_is_saved_and_public_url_is_returned(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = $this->fakeSignStorage();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [
                UploadedFile::fake()->image('ice-warning.png', 1024, 256),
            ],
        ]);

        $signId = $response->json('signs.0.id');
        $sign = Sign::query()->findOrFail($signId);

        $response->assertCreated()
            ->assertJsonPath('signs.0.public_url', Storage::disk($disk)->url($sign->storage_key))
            ->assertJsonMissingPath('signs.0.storage_key')
            ->assertJsonMissingPath('signs.0.storage_disk');

        $this->assertSame($disk, $sign->storage_disk);
        $this->assertNotEmpty($sign->storage_key);
        $this->assertSame('image/png', $sign->mime_type);
        $this->assertSame(1024, $sign->width);
        $this->assertSame(256, $sign->height);
        $this->assertGreaterThan(0, $sign->size_bytes);
    }

    public function test_uploading_a_static_image_generates_a_webp_thumbnail(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = $this->fakeSignStorage();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [
                UploadedFile::fake()->image('ice-warning.png', 1024, 256),
            ],
        ]);

        $response->assertCreated();

        $sign = Sign::query()->where('name', 'ice-warning')->firstOrFail();

        $this->assertNotNull($sign->thumbnail_url);
        $this->assertNotSame($sign->public_url, $sign->thumbnail_url);
        $this->assertStringEndsWith('.webp', $sign->thumbnail_url);

        $defaultVariant = $folder->defaultVariant;
        $thumbnailKey = "signs/{$user->id}/{$folder->id}/{$defaultVariant->id}/ice-warning-1024x256-thumb.webp";

        $this->assertSame(Storage::disk($disk)->url($thumbnailKey), $sign->thumbnail_url);
        Storage::disk($disk)->assertExists($thumbnailKey);
        $this->assertSame('public', Storage::disk($disk)->getVisibility($thumbnailKey));
        $response->assertJsonPath('signs.0.thumbnail_url', $sign->thumbnail_url);
    }

    public function test_uploading_a_video_does_not_generate_a_thumbnail(): void
    {
        $disk = $this->fakeSignStorage();

        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Animated Signs',
            'slug' => 'animated-signs',
        ]);

        Sanctum::actingAs($user);

        $file = $this->makeWebmUpload('wave-banner.webm', 320, 160);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [$file],
        ])->assertCreated()
            ->assertJsonPath('signs.0.thumbnail_url', null);

        $sign = Sign::query()->where('name', 'wave-banner')->firstOrFail();

        $this->assertNull($sign->thumbnail_url);
        $this->assertSame($disk, $sign->storage_disk);
    }

    private function fakeSignStorage(): string
    {
        $disk = config('filesystems.default');

        Storage::fake($disk);

        return $disk;
    }
}
