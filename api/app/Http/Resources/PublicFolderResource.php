<?php

namespace App\Http\Resources;

use App\Enums\FolderVisibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicFolderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->public_slug,
            'visibility' => $this->visibility instanceof FolderVisibility
                ? $this->visibility->value
                : $this->visibility,
            'user_id' => $this->user_id,
            'owner' => [
                'discord_username' => $this->user->discord_username,
                'discord_global_name' => $this->user->discord_global_name,
                'discord_avatar' => $this->user->discord_avatar,
            ],
        ];
    }
}
