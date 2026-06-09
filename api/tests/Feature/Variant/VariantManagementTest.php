<?php

namespace Tests\Feature\Variant;

use App\Enums\FolderVisibility;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VariantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_folder_has_default_variant_on_creation(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/folders', [
            'name' => 'Test Folder',
            'visibility' => FolderVisibility::Private->value,
        ]);

        $response->assertCreated();
        $folderId = $response->json('id');

        $this->assertDatabaseHas('variants', [
            'folder_id' => $folderId,
            'name' => 'Default',
            'is_default' => true,
        ]);

        $variantsResponse = $this->getJson("/api/folders/{$folderId}/variants");
        $variantsResponse->assertOk();
        $variants = $variantsResponse->json();
        $this->assertCount(1, $variants);
        $this->assertEquals('Default', $variants[0]['name']);
        $this->assertTrue($variants[0]['is_default']);
    }

    public function test_user_can_create_named_variant(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/folders/{$folder->id}/variants", [
            'name' => 'Blue',
            'grid_background_preset' => 'dark',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Blue')
            ->assertJsonPath('is_default', false)
            ->assertJsonPath('grid_background_preset', 'dark')
            ->assertJsonPath('backfill_performed', false);

        $this->assertDatabaseHas('variants', [
            'folder_id' => $folder->id,
            'name' => 'Blue',
            'is_default' => false,
            'grid_background_preset' => 'dark',
        ]);
    }

    public function test_user_cannot_create_duplicate_variant_name(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/folders/{$folder->id}/variants", ['name' => 'Blue'])->assertCreated();

        $this->postJson("/api/folders/{$folder->id}/variants", ['name' => 'Blue'])->assertStatus(422);
    }

    public function test_user_cannot_create_variant_in_another_users_folder(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($other);

        $this->postJson("/api/folders/{$folder->id}/variants", ['name' => 'Blue'])->assertStatus(403);
    }

    public function test_user_can_list_variants(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        $response = $this->getJson("/api/folders/{$folder->id}/variants");

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_user_can_rename_variant(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $variant = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/folders/{$folder->id}/variants/{$variant->id}", [
            'name' => 'Cyan',
        ])->assertOk();

        $this->assertDatabaseHas('variants', [
            'id' => $variant->id,
            'name' => 'Cyan',
        ]);
    }

    public function test_user_cannot_update_variant_in_another_users_folder(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $owner->id]);
        $variant = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($other);

        $this->patchJson("/api/folders/{$folder->id}/variants/{$variant->id}", [
            'name' => 'Cyan',
        ])->assertStatus(403);
    }

    public function test_updating_variant_with_mismatched_folder_returns_not_found(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $otherFolder = Folder::factory()->create(['user_id' => $user->id]);
        $variant = $otherFolder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/folders/{$folder->id}/variants/{$variant->id}", [
            'name' => 'Cyan',
        ])->assertNotFound();
    }

    public function test_user_can_update_variant_grid_background_preset(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $variant = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/folders/{$folder->id}/variants/{$variant->id}", [
            'grid_background_preset' => 'medium',
        ])
            ->assertOk()
            ->assertJsonPath('grid_background_preset', 'medium');

        $this->assertDatabaseHas('variants', [
            'id' => $variant->id,
            'grid_background_preset' => 'medium',
        ]);
    }

    public function test_variant_grid_background_preset_must_be_predefined(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $variant = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/folders/{$folder->id}/variants/{$variant->id}", [
            'grid_background_preset' => 'magenta',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['grid_background_preset']);
    }

    public function test_user_can_set_named_variant_as_default(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $variant = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/folders/{$folder->id}/variants/{$variant->id}", [
            'is_default' => true,
        ])->assertOk();

        $variant->refresh();
        $defaultVariant = $folder->defaultVariant;

        $this->assertTrue($variant->is_default);
        $this->assertNull($variant->name);
        $this->assertNotNull($defaultVariant);
        $this->assertEquals($variant->id, $defaultVariant->id);

        $this->assertDatabaseHas('variants', [
            'folder_id' => $folder->id,
            'name' => 'Original',
            'is_default' => false,
        ]);
    }

    public function test_user_cannot_delete_default_variant_directly(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $defaultVariant = $folder->defaultVariant;

        Sanctum::actingAs($user);

        $this->deleteJson("/api/folders/{$folder->id}/variants/{$defaultVariant->id}")
            ->assertStatus(409);
    }

    public function test_user_cannot_delete_last_variant(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $variant = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        $folder->defaultVariant->delete();

        Sanctum::actingAs($user);

        $this->deleteJson("/api/folders/{$folder->id}/variants/{$variant->id}")
            ->assertStatus(409);
    }

    public function test_user_can_delete_named_variant(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $variant = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/folders/{$folder->id}/variants/{$variant->id}")
            ->assertOk();

        $this->assertDatabaseMissing('variants', ['id' => $variant->id]);
    }

    public function test_user_cannot_delete_variant_in_another_users_folder(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $owner->id]);
        $variant = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($other);

        $this->deleteJson("/api/folders/{$folder->id}/variants/{$variant->id}")
            ->assertStatus(403);
    }

    public function test_deleting_variant_with_mismatched_folder_returns_not_found(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $otherFolder = Folder::factory()->create(['user_id' => $user->id]);
        $variant = $otherFolder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/folders/{$folder->id}/variants/{$variant->id}")
            ->assertNotFound();
    }

    public function test_uploading_sign_assigns_to_variant(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $defaultVariant = $folder->defaultVariant;

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('test.png', 100, 100);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'files' => [$file],
        ])->assertCreated();

        $this->assertDatabaseHas('signs', [
            'folder_id' => $folder->id,
            'variant_id' => $defaultVariant->id,
            'name' => 'test',
        ]);
    }

    public function test_uploading_sign_to_specific_variant(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $variant = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('test.png', 100, 100);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'variant_id' => $variant->id,
            'files' => [$file],
        ])->assertCreated();

        $this->assertDatabaseHas('signs', [
            'folder_id' => $folder->id,
            'variant_id' => $variant->id,
            'name' => 'test',
        ]);
    }

    public function test_user_cannot_upload_sign_to_variant_from_another_folder(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $otherFolder = Folder::factory()->create(['user_id' => $user->id]);
        $otherVariant = $otherFolder->variants()->create([
            'name' => 'Blue',
            'is_default' => false,
            'sort_order' => 1,
        ]);

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('test.png', 100, 100);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'variant_id' => $otherVariant->id,
            'files' => [$file],
        ])->assertStatus(422);
    }

    public function test_storage_key_includes_variant_id(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $variant = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('test.png', 100, 100);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'variant_id' => $variant->id,
            'files' => [$file],
        ])->assertCreated();

        $sign = $folder->signs()->first();

        $this->assertNotNull($sign);
        $this->assertStringContainsString("/{$variant->id}/", $sign->storage_key);
    }

    public function test_uploading_same_name_to_different_variants_creates_separate_signs(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $blue = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);
        $red = $folder->variants()->create(['name' => 'Red', 'is_default' => false, 'sort_order' => 2]);

        Sanctum::actingAs($user);

        $file1 = UploadedFile::fake()->image('logo.png', 100, 100);
        $file2 = UploadedFile::fake()->image('logo.png', 100, 100);

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'variant_id' => $blue->id,
            'files' => [$file1],
        ])->assertCreated();

        $this->postJson("/api/folders/{$folder->id}/signs", [
            'variant_id' => $red->id,
            'files' => [$file2],
        ])->assertCreated();

        $this->assertEquals(2, $folder->signs()->count());
        $this->assertEquals(1, $folder->signs()->where('variant_id', $blue->id)->count());
        $this->assertEquals(1, $folder->signs()->where('variant_id', $red->id)->count());
    }

    public function test_user_can_change_sign_variant(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $default = $folder->defaultVariant;
        $blue = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);
        $sign = $folder->signs()->create([
            'user_id' => $user->id,
            'name' => 'test',
            'variant_id' => $default->id,
            'storage_disk' => 'public',
            'storage_key' => 'signs/1/1/test.png',
            'public_url' => 'http://localhost/storage/signs/1/1/test.png',
            'mime_type' => 'image/png',
            'size_bytes' => 100,
            'width' => 100,
            'height' => 100,
            'column_ratio' => 1,
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/signs/variant', [
            'ids' => [$sign->id],
            'variant_id' => $blue->id,
        ])->assertOk();

        $this->assertDatabaseHas('signs', [
            'id' => $sign->id,
            'variant_id' => $blue->id,
        ]);
    }

    public function test_moving_signs_resets_variant_to_target_folder_default(): void
    {
        $user = User::factory()->create();
        $sourceFolder = Folder::factory()->create(['user_id' => $user->id]);
        $targetFolder = Folder::factory()->create(['user_id' => $user->id]);
        $sourceVariant = $sourceFolder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);
        $targetDefault = $targetFolder->defaultVariant;

        $sign = $sourceFolder->signs()->create([
            'user_id' => $user->id,
            'name' => 'test',
            'variant_id' => $sourceVariant->id,
            'storage_disk' => 'public',
            'storage_key' => 'signs/1/1/test.png',
            'public_url' => 'http://localhost/storage/signs/1/1/test.png',
            'mime_type' => 'image/png',
            'size_bytes' => 100,
            'width' => 100,
            'height' => 100,
            'column_ratio' => 1,
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/signs/move', [
            'ids' => [$sign->id],
            'folder_id' => $targetFolder->id,
        ])->assertOk();

        $this->assertDatabaseHas('signs', [
            'id' => $sign->id,
            'folder_id' => $targetFolder->id,
            'variant_id' => $targetDefault->id,
        ]);
    }

    public function test_public_folder_shows_variants(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create([
            'user_id' => $user->id,
            'visibility' => FolderVisibility::Public,
        ]);
        $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);

        $response = $this->getJson("/api/public/folders/{$folder->public_slug}");

        $response->assertOk();
        $response->assertJsonStructure([
            'folder' => [
                'variants' => [
                    '*' => ['id', 'name', 'is_default', 'grid_background_preset'],
                ],
            ],
        ]);
        $this->assertCount(2, $response->json('folder.variants'));
    }

    public function test_public_folder_index_exposes_default_variant_grid_background_preset(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create([
            'user_id' => $user->id,
            'visibility' => FolderVisibility::Public,
        ]);
        $folder->defaultVariant->update(['grid_background_preset' => 'medium']);
        $folder->signs()->create([
            'user_id' => $user->id,
            'name' => 'default-sign',
            'variant_id' => $folder->defaultVariant->id,
            'storage_disk' => 'public',
            'storage_key' => 'signs/1/1/test.png',
            'public_url' => 'http://localhost/storage/signs/1/1/test.png',
            'mime_type' => 'image/png',
            'size_bytes' => 100,
            'width' => 100,
            'height' => 100,
            'column_ratio' => 1,
        ]);

        $this->getJson('/api/public/folders')
            ->assertOk()
            ->assertJsonPath('data.0.preview_grid_background_preset', 'medium');
    }

    public function test_public_folder_index_previews_default_variant_signs_only(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create([
            'user_id' => $user->id,
            'visibility' => FolderVisibility::Public,
        ]);
        $folder->signs()->create([
            'user_id' => $user->id,
            'name' => 'default-sign',
            'variant_id' => $folder->defaultVariant->id,
            'storage_disk' => 'public',
            'storage_key' => 'signs/1/1/test.png',
            'public_url' => 'http://localhost/storage/signs/1/1/test.png',
            'mime_type' => 'image/png',
            'size_bytes' => 100,
            'width' => 100,
            'height' => 100,
            'column_ratio' => 1,
        ]);

        $blue = $folder->variants()->create(['name' => 'Blue', 'is_default' => false, 'sort_order' => 1]);
        $folder->signs()->create([
            'user_id' => $user->id,
            'name' => 'blue-sign',
            'variant_id' => $blue->id,
            'storage_disk' => 'public',
            'storage_key' => 'signs/1/1/test.png',
            'public_url' => 'http://localhost/storage/signs/1/1/test.png',
            'mime_type' => 'image/png',
            'size_bytes' => 100,
            'width' => 100,
            'height' => 100,
            'column_ratio' => 1,
        ]);

        $response = $this->getJson('/api/public/folders');

        $response->assertOk();
        $previewNames = collect($response->json('data.0.preview_signs'))->pluck('name');
        $this->assertContains('default-sign', $previewNames);
        $this->assertNotContains('blue-sign', $previewNames);
    }
}
