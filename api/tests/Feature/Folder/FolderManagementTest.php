<?php

namespace Tests\Feature\Folder;

use App\Enums\FolderVisibility;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FolderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_folder(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/folders', [
            'name' => 'Club Signs',
            'visibility' => FolderVisibility::Private->value,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'name',
                'slug',
                'visibility',
                'created_at',
                'updated_at',
            ])
            ->assertJsonPath('name', 'Club Signs')
            ->assertJsonPath('slug', 'club-signs')
            ->assertJsonPath('visibility', FolderVisibility::Private->value)
            ->assertJsonMissingPath('password_hash');

        $this->assertDatabaseHas('folders', [
            'user_id' => $user->id,
            'name' => 'Club Signs',
            'slug' => 'club-signs',
            'visibility' => FolderVisibility::Private->value,
        ]);
    }

    public function test_authenticated_user_can_list_own_folders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownFolder = Folder::factory()->for($user)->create([
            'name' => 'Own Folder',
            'slug' => 'own-folder',
            'visibility' => FolderVisibility::Private,
        ]);

        Folder::factory()->for($otherUser)->create([
            'name' => 'Other Folder',
            'slug' => 'other-folder',
            'visibility' => FolderVisibility::Public,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/folders')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $ownFolder->id)
            ->assertJsonPath('0.slug', 'own-folder')
            ->assertJsonMissingPath('0.password_hash');
    }

    public function test_authenticated_user_can_view_own_folder(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
            'visibility' => FolderVisibility::Private,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/folders/{$folder->id}")
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'slug',
                'visibility',
                'created_at',
                'updated_at',
            ])
            ->assertJsonPath('id', $folder->id)
            ->assertJsonPath('slug', 'club-signs')
            ->assertJsonMissingPath('password_hash');
    }

    public function test_authenticated_user_can_update_own_folder(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
            'visibility' => FolderVisibility::Password,
            'password_hash' => Hash::make('initial-password'),
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/folders/{$folder->id}", [
            'name' => 'Updated Club Signs',
            'visibility' => FolderVisibility::Private->value,
        ])
            ->assertOk()
            ->assertJsonPath('name', 'Updated Club Signs')
            ->assertJsonPath('slug', 'updated-club-signs')
            ->assertJsonPath('visibility', FolderVisibility::Private->value)
            ->assertJsonMissingPath('password_hash');

        $folder->refresh();

        $this->assertSame('Updated Club Signs', $folder->name);
        $this->assertSame('updated-club-signs', $folder->slug);
        $this->assertSame(FolderVisibility::Private->value, $folder->visibility->value);
        $this->assertNull($folder->password_hash);
    }

    public function test_authenticated_user_can_delete_own_folder(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
            'visibility' => FolderVisibility::Private,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/folders/{$folder->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'Folder deleted.',
            ]);

        $this->assertDatabaseMissing('folders', [
            'id' => $folder->id,
        ]);
    }

    public function test_user_cannot_view_another_users_folder(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $folder = Folder::factory()->for($otherUser)->create([
            'name' => 'Private Folder',
            'slug' => 'private-folder',
            'visibility' => FolderVisibility::Private,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/folders/{$folder->id}")
            ->assertForbidden();
    }

    public function test_user_cannot_update_another_users_folder(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $folder = Folder::factory()->for($otherUser)->create([
            'name' => 'Private Folder',
            'slug' => 'private-folder',
            'visibility' => FolderVisibility::Private,
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/folders/{$folder->id}", [
            'name' => 'Updated Name',
            'visibility' => FolderVisibility::Public->value,
        ])
            ->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_folder(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $folder = Folder::factory()->for($otherUser)->create([
            'name' => 'Private Folder',
            'slug' => 'private-folder',
            'visibility' => FolderVisibility::Private,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/folders/{$folder->id}")
            ->assertForbidden();
    }

    public function test_password_folders_hash_passwords(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/folders', [
            'name' => 'Secret Club',
            'visibility' => FolderVisibility::Password->value,
            'password' => 'super-secret',
        ]);

        $response->assertCreated()
            ->assertJsonPath('visibility', FolderVisibility::Password->value)
            ->assertJsonMissingPath('password_hash');

        $folder = Folder::query()
            ->where('user_id', $user->id)
            ->where('slug', 'secret-club')
            ->firstOrFail();

        $this->assertNotNull($folder->password_hash);
        $this->assertTrue(Hash::check('super-secret', $folder->password_hash));
    }

    public function test_password_hash_is_never_exposed(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Hidden Folder',
            'slug' => 'hidden-folder',
            'visibility' => FolderVisibility::Password,
            'password_hash' => Hash::make('secret'),
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/folders')
            ->assertOk()
            ->assertJsonMissingPath('0.password_hash');

        $this->getJson("/api/folders/{$folder->id}")
            ->assertOk()
            ->assertJsonMissingPath('password_hash');
    }

    public function test_slug_generation_works(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/folders', [
            'name' => 'My Cool Signs',
            'visibility' => FolderVisibility::Private->value,
        ])
            ->assertCreated()
            ->assertJsonPath('slug', 'my-cool-signs');
    }

    public function test_duplicate_folder_names_generate_unique_slugs(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/folders', [
            'name' => 'Club Signs',
            'visibility' => FolderVisibility::Private->value,
        ])->assertCreated()
            ->assertJsonPath('slug', 'club-signs');

        $this->postJson('/api/folders', [
            'name' => 'Club Signs',
            'visibility' => FolderVisibility::Private->value,
        ])->assertCreated()
            ->assertJsonPath('slug', 'club-signs-2');

        $this->postJson('/api/folders', [
            'name' => 'Club Signs',
            'visibility' => FolderVisibility::Private->value,
        ])->assertCreated()
            ->assertJsonPath('slug', 'club-signs-3');
    }
}
