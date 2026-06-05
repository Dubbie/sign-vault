<?php

namespace App\Http\Resources;

use App\Enums\FolderVisibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $visibility = $this->visibility;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'public_slug' => $this->public_slug,
            'visibility' => $visibility instanceof FolderVisibility ? $visibility->value : $visibility,
            'attribution_name' => $this->attribution_name,
            'attribution_source_url' => $this->attribution_source_url,
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
            'variants' => $this->whenLoaded('variants', function (): array {
                return $this->variants->map(fn ($variant): array => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'is_default' => $variant->is_default,
                    'sort_order' => $variant->sort_order,
                ])->values()->all();
            }, []),
        ];
    }
}
