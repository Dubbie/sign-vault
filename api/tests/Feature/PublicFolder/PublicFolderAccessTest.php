<?php

namespace Tests\Feature\PublicFolder;

use App\Enums\FolderVisibility;
use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicFolderAccessTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertJsonMissingPath('folder.user_id')
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
            ->assertJsonMissingPath('folder.user_id')
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
            'name' => 'Ice Warning',
            'storage_disk' => 's3',
            'storage_key' => 'signs/'.$folder->user_id.'/'.$folder->slug.'/ice-warning.png',
            'public_url' => 'https://cdn.example.com/signs/ice-warning.png',
            'mime_type' => 'image/png',
            'size_bytes' => 12345,
            'width' => 1024,
            'height' => 256,
        ], $attributes));
    }
}
