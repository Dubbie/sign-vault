<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\SerializesFolderData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicFolderResource extends JsonResource
{
    use SerializesFolderData;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->public_slug,
            'visibility' => $this->serializeVisibility($this->visibility),
            'authors' => $this->whenLoaded('authors', fn (): array => $this->serializeAuthors($this->authors), []),
            'user_id' => $this->user_id,
            'owner' => $this->serializeOwner($this->user),
            'votes_count' => (int) ($this->votes_count ?? 0),
            'user_has_voted' => (bool) ($this->user_has_voted ?? false),
            'variants' => $this->whenLoaded('variants', fn (): array => $this->serializeVariants($this->variants), []),
        ];
    }
}
