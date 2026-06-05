<?php

namespace Tests\Feature\PublicFolder;

use App\Enums\FolderVisibility;
use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicFolderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_public_folders_with_signs(): void
    {
        $user = User::factory()->create();

        $publicWithSigns = Folder::factory()->for($user)->create([
            'name' => 'Public With Signs',
            'slug' => 'public-with-signs',
            'public_slug' => 'public-with-signs',
            'visibility' => FolderVisibility::Public,
        ]);
        Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $publicWithSigns->id,
            'variant_id' => $publicWithSigns->defaultVariant->id,
        ]);

        Folder::factory()->for(User::factory()->create())->create([
            'name' => 'Private Folder',
            'slug' => 'private-folder',
            'public_slug' => 'private-folder',
            'visibility' => FolderVisibility::Private,
        ]);

        Folder::factory()->for(User::factory()->create())->create([
            'name' => 'Password Folder',
            'slug' => 'password-folder',
            'public_slug' => 'password-folder',
            'visibility' => FolderVisibility::Password,
        ]);

        $publicEmpty = Folder::factory()->for(User::factory()->create())->create([
            'name' => 'Public Empty',
            'slug' => 'public-empty',
            'public_slug' => 'public-empty',
            'visibility' => FolderVisibility::Public,
        ]);

        $this->getJson('/api/public/folders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Public With Signs')
            ->assertJsonMissingPath('data.0.password_hash');
    }

    public function test_empty_public_folder_is_excluded(): void
    {
        $user = User::factory()->create();

        Folder::factory()->for($user)->create([
            'name' => 'Empty Folder',
            'slug' => 'empty-folder',
            'public_slug' => 'empty-folder',
            'visibility' => FolderVisibility::Public,
        ]);

        $this->getJson('/api/public/folders')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_includes_owner_information(): void
    {
        $user = User::factory()->create([
            'discord_username' => 'janedoe',
            'discord_global_name' => 'Jane Doe',
            'discord_avatar' => 'https://cdn.discord.com/avatars/123/abc.png',
        ]);

        $folder = Folder::factory()->for($user)->create([
            'name' => 'Jane Signs',
            'slug' => 'jane-signs',
            'public_slug' => 'jane-signs',
            'visibility' => FolderVisibility::Public,
        ]);

        Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $folder->defaultVariant->id,
        ]);

        $this->getJson('/api/public/folders')
            ->assertOk()
            ->assertJsonPath('data.0.owner.discord_username', 'janedoe')
            ->assertJsonPath('data.0.owner.discord_global_name', 'Jane Doe')
            ->assertJsonPath('data.0.owner.discord_avatar', 'https://cdn.discord.com/avatars/123/abc.png');
    }

    public function test_includes_sign_count(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Folder With Signs',
            'slug' => 'folder-with-signs',
            'public_slug' => 'folder-with-signs',
            'visibility' => FolderVisibility::Public,
        ]);

        Sign::factory()->count(5)->create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $folder->defaultVariant->id,
        ]);

        $this->getJson('/api/public/folders')
            ->assertOk()
            ->assertJsonPath('data.0.signs_count', 5);
    }

    public function test_includes_variant_count(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Folder With Variants',
            'slug' => 'folder-with-variants',
            'public_slug' => 'folder-with-variants',
            'visibility' => FolderVisibility::Public,
        ]);

        Variant::factory()->for($folder)->named('Alt')->create();
        Variant::factory()->for($folder)->named('Compact')->create();
        Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $folder->defaultVariant->id,
        ]);

        $this->getJson('/api/public/folders')
            ->assertOk()
            ->assertJsonPath('data.0.variants_count', 3);
    }

    public function test_includes_preview_signs(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create([
            'name' => 'Folder With Previews',
            'slug' => 'folder-with-previews',
            'public_slug' => 'folder-with-previews',
            'visibility' => FolderVisibility::Public,
        ]);

        Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'width' => 200,
            'height' => 200,
            'variant_id' => $folder->defaultVariant->id,
        ]);
        Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'width' => 200,
            'height' => 100,
            'variant_id' => $folder->defaultVariant->id,
        ]);
        Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'width' => 400,
            'height' => 100,
            'variant_id' => $folder->defaultVariant->id,
        ]);
        Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'width' => 400,
            'height' => 100,
            'variant_id' => $folder->defaultVariant->id,
        ]);
        Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'width' => 600,
            'height' => 100,
            'variant_id' => $folder->defaultVariant->id,
        ]);

        $response = $this->getJson('/api/public/folders')
            ->assertOk();

        $previewSigns = $response->json('data.0.preview_signs');

        $this->assertCount(5, $previewSigns);
        $this->assertArrayHasKey('id', $previewSigns[0]);
        $this->assertArrayHasKey('name', $previewSigns[0]);
        $this->assertArrayHasKey('public_url', $previewSigns[0]);
        $this->assertArrayHasKey('width', $previewSigns[0]);
        $this->assertArrayHasKey('height', $previewSigns[0]);
        $this->assertSame(200, $previewSigns[0]['width']);
        $this->assertSame(200, $previewSigns[0]['height']);
        $this->assertSame(200, $previewSigns[1]['width']);
        $this->assertSame(100, $previewSigns[1]['height']);
        $this->assertSame(400, $previewSigns[2]['width']);
        $this->assertSame(100, $previewSigns[2]['height']);
        $this->assertSame(400, $previewSigns[3]['width']);
        $this->assertSame(100, $previewSigns[3]['height']);
        $this->assertSame(600, $previewSigns[4]['width']);
        $this->assertSame(100, $previewSigns[4]['height']);
    }

    public function test_can_search_by_folder_name(): void
    {
        $user = User::factory()->create();

        $club = Folder::factory()->for($user)->create([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
            'public_slug' => 'club-signs',
            'visibility' => FolderVisibility::Public,
        ]);
        Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $club->id,
            'variant_id' => $club->defaultVariant->id,
        ]);

        $racing = Folder::factory()->for($user)->create([
            'name' => 'Racing Signs',
            'slug' => 'racing-signs',
            'public_slug' => 'racing-signs',
            'visibility' => FolderVisibility::Public,
        ]);
        Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $racing->id,
            'variant_id' => $racing->defaultVariant->id,
        ]);

        $this->getJson('/api/public/folders?q=club')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Club Signs');

        $this->getJson('/api/public/folders?q=racing')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Racing Signs');

        $this->getJson('/api/public/folders?q=nonexistent')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_is_paginated(): void
    {
        $user = User::factory()->create();
        Folder::factory()->count(25)->for($user)->create([
            'visibility' => FolderVisibility::Public,
        ])->each(function (Folder $folder) use ($user): void {
            Sign::factory()->create([
                'user_id' => $user->id,
                'folder_id' => $folder->id,
                'variant_id' => $folder->defaultVariant->id,
            ]);
        });

        $this->getJson('/api/public/folders')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_public_folder_can_be_viewed_anonymously(): void
    {
        $folder = $this->makeFolder(FolderVisibility::Public, [
            'name' => 'Club Signs',
            'slug' => 'club-signs',
            'public_slug' => 'club-signs-public',
        ]);
        $this->makeSign($folder, [
            'name' => 'Ice Warning',
            'public_url' => 'https://cdn.example.com/signs/ice-warning.png',
        ]);

        $this->getJson('/api/public/folders/'.$folder->public_slug)
            ->assertOk()
            ->assertJsonStructure([
                'folder' => [
                    'id',
                    'name',
                    'slug',
                    'visibility',
                ],
                'signs' => [
                    '*' => [
                        'id',
                        'name',
                        'public_url',
                        'mime_type',
                        'width',
                        'height',
                    ],
                ],
            ])
            ->assertJsonPath('folder.name', 'Club Signs')
            ->assertJsonPath('folder.slug', 'club-signs-public')
            ->assertJsonPath('folder.visibility', FolderVisibility::Public->value)
            ->assertJsonPath('signs.0.name', 'Ice Warning')
            ->assertJsonPath('signs.0.public_url', 'https://cdn.example.com/signs/ice-warning.png')
            ->assertJsonPath('folder.user_id', $folder->user_id)
            ->assertJsonPath('folder.owner.discord_username', $folder->user->discord_username)
            ->assertJsonMissingPath('folder.password_hash')
            ->assertJsonMissingPath('signs.0.storage_key')
            ->assertJsonMissingPath('signs.0.storage_disk');
    }

    public function test_private_folder_returns_404(): void
    {
        $folder = $this->makeFolder(FolderVisibility::Private, [
            'name' => 'Private Signs',
            'slug' => 'private-signs',
            'public_slug' => 'private-signs-public',
        ]);

        $this->getJson('/api/public/folders/'.$folder->public_slug)
            ->assertNotFound();
    }

    public function test_password_folder_indicates_password_required(): void
    {
        $folder = $this->makeFolder(FolderVisibility::Password, [
            'name' => 'Secret Signs',
            'slug' => 'secret-signs',
            'public_slug' => 'secret-signs-public',
            'password_hash' => Hash::make('super-secret'),
        ]);

        $this->getJson('/api/public/folders/'.$folder->public_slug)
            ->assertOk()
            ->assertJson([
                'requires_password' => true,
            ])
            ->assertJsonMissingPath('folder')
            ->assertJsonMissingPath('signs');
    }

    public function test_password_folder_cannot_be_viewed_without_password(): void
    {
        $folder = $this->makeFolder(FolderVisibility::Password, [
            'name' => 'Secret Signs',
            'slug' => 'secret-signs',
            'public_slug' => 'secret-signs-public',
            'password_hash' => Hash::make('super-secret'),
        ]);

        $this->postJson('/api/public/folders/'.$folder->public_slug.'/unlock')
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_password_folder_rejects_invalid_password(): void
    {
        $folder = $this->makeFolder(FolderVisibility::Password, [
            'name' => 'Secret Signs',
            'slug' => 'secret-signs',
            'public_slug' => 'secret-signs-public',
            'password_hash' => Hash::make('super-secret'),
        ]);

        $this->postJson('/api/public/folders/'.$folder->public_slug.'/unlock', [
            'password' => 'wrong-password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_password_folder_returns_contents_with_valid_password(): void
    {
        $folder = $this->makeFolder(FolderVisibility::Password, [
            'name' => 'Secret Signs',
            'slug' => 'secret-signs',
            'public_slug' => 'secret-signs-public',
            'password_hash' => Hash::make('super-secret'),
        ]);
        $this->makeSign($folder, [
            'name' => 'Hidden Banner',
            'public_url' => 'https://cdn.example.com/signs/hidden-banner.png',
        ]);

        $this->postJson('/api/public/folders/'.$folder->public_slug.'/unlock', [
            'password' => 'super-secret',
        ])
            ->assertOk()
            ->assertJsonPath('folder.name', 'Secret Signs')
            ->assertJsonPath('folder.slug', 'secret-signs-public')
            ->assertJsonPath('folder.visibility', FolderVisibility::Password->value)
            ->assertJsonPath('signs.0.name', 'Hidden Banner')
            ->assertJsonPath('signs.0.public_url', 'https://cdn.example.com/signs/hidden-banner.png')
            ->assertJsonPath('folder.user_id', $folder->user_id)
            ->assertJsonPath('folder.owner.discord_username', $folder->user->discord_username)
            ->assertJsonMissingPath('folder.password_hash')
            ->assertJsonMissingPath('signs.0.storage_key')
            ->assertJsonMissingPath('signs.0.storage_disk');
    }

    public function test_public_folder_returns_signs_without_authentication(): void
    {
        $folder = $this->makeFolder(FolderVisibility::Public, [
            'name' => 'Public Signs',
            'slug' => 'public-signs',
            'public_slug' => 'public-signs-public',
        ]);
        $this->makeSign($folder, [
            'name' => 'Ice Warning',
            'public_url' => 'https://cdn.example.com/signs/ice-warning.png',
        ]);
        $this->makeSign($folder, [
            'name' => 'Road Closed',
            'public_url' => 'https://cdn.example.com/signs/road-closed.png',
        ]);

        $this->getJson('/api/public/folders/'.$folder->public_slug)
            ->assertOk()
            ->assertJsonCount(2, 'signs')
            ->assertJsonPath('signs.0.name', 'Road Closed')
            ->assertJsonPath('signs.1.name', 'Ice Warning');
    }

    private function makeFolder(FolderVisibility $visibility, array $attributes = []): Folder
    {
        $user = User::factory()->create();

        return Folder::factory()->for($user)->create(array_merge([
            'name' => 'Club Signs',
            'slug' => 'club-signs',
            'public_slug' => 'club-signs-public',
            'visibility' => $visibility,
            'password_hash' => null,
        ], $attributes));
    }

    private function makeSign(Folder $folder, array $attributes = []): Sign
    {
        return Sign::create(array_merge([
            'user_id' => $folder->user_id,
            'folder_id' => $folder->id,
            'variant_id' => $folder->defaultVariant->id,
            'name' => 'Ice Warning',
            'storage_disk' => 's3',
            'storage_key' => 'signs/'.$folder->user_id.'/'.$folder->id.'/ice-warning.png',
            'public_url' => 'https://cdn.example.com/signs/ice-warning.png',
            'mime_type' => 'image/png',
            'size_bytes' => 12345,
            'width' => 1024,
            'height' => 256,
        ], $attributes));
    }
}
