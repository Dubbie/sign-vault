<?php

namespace App\Services;

use App\Enums\FolderVisibility;
use App\Models\Folder;
use Illuminate\Support\Facades\Hash;

class FolderService
{
    /**
     * Return a bcrypt hash only when the visibility requires a password.
     *
     * @param array{visibility: string, password?: string|null} $validated
     */
    public function hashPassword(array $validated): ?string
    {
        if ($validated['visibility'] !== FolderVisibility::Password->value) {
            return null;
        }

        return Hash::make($validated['password']);
    }

    /**
     * Generate a public_slug when the folder transitions from Private to a public-facing visibility.
     * Returns null when no slug change is needed.
     *
     * @param array{name: string, visibility: string} $validated
     */
    public function resolvePublicSlug(Folder $folder, array $validated): ?string
    {
        $wasPrivate           = $folder->visibility === FolderVisibility::Private;
        $isBecomingPublicFacing = $validated['visibility'] !== FolderVisibility::Private->value;

        if ($wasPrivate && $isBecomingPublicFacing) {
            return Folder::generatePublicSlugFor($validated['name'], $folder->id);
        }

        return null;
    }
}
