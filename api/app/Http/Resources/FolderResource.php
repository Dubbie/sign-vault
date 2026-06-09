<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\SerializesFolderData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
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
            'slug' => $this->slug,
            'public_slug' => $this->public_slug,
            'visibility' => $this->serializeVisibility($this->visibility),
            'authors' => $this->whenLoaded('authors', fn (): array => $this->serializeAuthors($this->authors), []),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
            'variants' => $this->whenLoaded('variants', fn (): array => $this->serializeVariants($this->variants, true), []),
        ];
    }
}
