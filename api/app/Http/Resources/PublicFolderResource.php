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
            'authors' => $this->whenLoaded('authors', fn (): array => $this->authors->map(
                fn ($author): array => [
                    'id' => $author->id,
                    'name' => $author->name,
                    'source_url' => $author->source_url,
                    'sort_order' => $author->sort_order,
                ],
            )->values()->all(), []),
            'user_id' => $this->user_id,
            'owner' => [
                'display_name' => $this->user->display_name,
                'avatar_url' => $this->user->avatar_url,
            ],
            'votes_count' => (int) ($this->votes_count ?? 0),
            'user_has_voted' => (bool) ($this->user_has_voted ?? false),
            'variants' => $this->whenLoaded('variants', function (): array {
                return $this->variants->map(fn ($variant): array => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'is_default' => $variant->is_default,
                    'grid_background_preset' => $variant->grid_background_preset,
                ])->values()->all();
            }, []),
        ];
    }
}
