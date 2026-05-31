<?php

namespace Tests\Feature\Sign;

use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery;
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
        Log::spy();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/folders/{$folder->id}/signs", [
            'file' => UploadedFile::fake()->image('ice-warning.png', 1024, 256),
            'name' => 'Ice Warning',
            'description' => null,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'folder_id',
                'name',
                'description',
                'public_url',
                'mime_type',
                'size_bytes',
                'width',
                'height',
                'created_at',
                'updated_at',
            ])
            ->assertJsonPath('folder_id', $folder->id)
            ->assertJsonPath('name', 'Ice Warning')
            ->assertJsonPath('description', null)
            ->assertJsonPath('mime_type', 'image/png')
            ->assertJsonPath('width', 1024)
            ->assertJsonPath('height', 256)
            ->assertJsonMissingPath('storage_key')
            ->assertJsonMissingPath('storage_disk');

        $sign = Sign::query()->firstOrFail();

        $this->assertSame($user->id, $sign->user_id);
        $this->assertSame($folder->id, $sign->folder_id);
        $this->assertSame('Ice Warning', $sign->name);
        $this->assertSame('image/png', $sign->mime_type);
        $this->assertSame(1024, $sign->width);
        $this->assertSame(256, $sign->height);
        $this->assertGreaterThan(0, $sign->size_bytes);
        $this->assertSame($disk, $sign->storage_disk);
        $this->assertSame(Storage::disk($disk)->url($sign->storage_key), $sign->public_url);
        Storage::disk($disk)->assertExists($sign->storage_key);
        $this->assertSame('public', Storage::disk($disk)->getVisibility($sign->storage_key));

        Log::shouldHaveReceived('info')
            ->with(
                'Sign upload started.',
                Mockery::on(function (array $context) use ($user, $folder, $disk): bool {
                    return $context['user_id'] === $user->id
                        && $context['folder_id'] === $folder->id
                        && $context['folder_slug'] === $folder->slug
                        && $context['disk'] === $disk
                        && $context['file_name'] === 'ice-warning.png'
                        && $context['derived_name'] === 'Ice Warning'
                        && $context['mime_type'] === 'image/png';
                })
            )
            ->once();

        Log::shouldHaveReceived('info')
            ->with(
                'Sign upload stored.',
                Mockery::on(function (array $context) use ($disk, $sign): bool {
                    return $context['disk'] === $disk
                        && $context['storage_key'] === $sign->storage_key
                        && $context['directory'] === 'signs/'.$sign->user_id.'/'.$sign->folder->slug
                        && str_ends_with($context['filename'], '.png');
                })
            )
            ->once();
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
            'name' => 'Ice Warning',
            'description' => null,
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
            'name' => 'Ice Warning',
            'description' => 'Track ahead',
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
                'description',
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
            ->assertJsonPath('name', 'Ice Warning')
            ->assertJsonPath('description', 'Track ahead')
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
            'name' => 'Ice Warning',
            'description' => null,
            'storage_disk' => $disk,
            'storage_key' => $storageKey,
            'public_url' => Storage::disk($disk)->url($storageKey),
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 1024,
            'height' => 256,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/signs/{$sign->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'Sign deleted.',
            ]);

        $this->assertDatabaseMissing('signs', [
            'id' => $sign->id,
        ]);
        Storage::disk($disk)->assertMissing($storageKey);
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
            'name' => 'Ice Warning',
            'description' => null,
            'storage_disk' => $disk,
            'storage_key' => $storageKey,
            'public_url' => Storage::disk($disk)->url($storageKey),
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 1024,
            'height' => 256,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/signs/{$sign->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'Sign deleted.',
            ]);

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
            'name' => 'Ice Warning',
            'description' => null,
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
            'name' => 'Ice Warning',
            'description' => null,
            'storage_disk' => 's3',
            'storage_key' => 'signs/2/private-folder/ice-warning.png',
            'public_url' => 'http://example.test/signs/2/private-folder/ice-warning.png',
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'width' => 1024,
            'height' => 256,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/signs/{$sign->id}")
            ->assertForbidden();
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
            'file' => UploadedFile::fake()->create('document.txt', 10, 'text/plain'),
            'name' => 'Ice Warning',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
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
            'name' => 'Ice Warning',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
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
            'file' => UploadedFile::fake()->image('start-banner.png', 1024, 256),
        ])
            ->assertCreated()
            ->assertJsonPath('name', 'start-banner');

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
            'file' => UploadedFile::fake()->image('ice-warning.png', 1024, 256),
            'name' => 'Ice Warning',
        ]);

        $signId = $response->json('id');
        $sign = Sign::query()->findOrFail($signId);

        $response->assertCreated()
            ->assertJsonPath('public_url', Storage::disk($disk)->url($sign->storage_key))
            ->assertJsonMissingPath('storage_key')
            ->assertJsonMissingPath('storage_disk');

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
