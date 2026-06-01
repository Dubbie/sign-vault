<?php

namespace Tests\Feature\Sign;

use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SignManagementTest extends TestCase
{
    use RefreshDatabase;

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
        $storageKey = 'signs/'.$user->id.'/'.$folder->slug.'/ice-warning.png';

        Storage::disk($disk)->put($storageKey, 'fake-image');

        $sign = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
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
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $sign->id)
            ->assertJsonPath('0.folder_id', $folder->id)
            ->assertJsonPath('0.public_url', Storage::disk($disk)->url($storageKey))
            ->assertJsonMissingPath('0.storage_key')
            ->assertJsonMissingPath('0.storage_disk');
    }

    public function test_authenticated_user_can_view_own_sign(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
        ]);

        $disk = $this->fakeSignStorage();
        $storageKey = 'signs/'.$user->id.'/'.$folder->slug.'/ice-warning.png';

        Storage::disk($disk)->put($storageKey, 'fake-image');

        $sign = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
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
        $storageKey = 'signs/'.$user->id.'/'.$folder->slug.'/ice-warning.png';

        Storage::disk($disk)->put($storageKey, 'fake-image');

        $sign = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
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
        $storageKey = 'signs/'.$user->id.'/'.$folder->slug.'/ice-warning.png';

        $sign = Sign::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
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
            'name' => 'ice-warning',
            'storage_disk' => 's3',
            'storage_key' => 'signs/2/private-folder/ice-warning.png',
            'public_url' => 'http://example.test/signs/2/private-folder/ice-warning.png',
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
            'name' => 'ice-warning',
            'storage_disk' => 's3',
            'storage_key' => 'signs/2/private-folder/ice-warning.png',
            'public_url' => 'http://example.test/signs/2/private-folder/ice-warning.png',
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

    private function fakeSignStorage(): string
    {
        $disk = config('filesystems.default');

        Storage::fake($disk);

        return $disk;
    }
}
