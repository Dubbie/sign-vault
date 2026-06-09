<?php

namespace App\Http\Resources\Concerns;

use App\Enums\FolderVisibility;
use App\Models\FolderAuthor;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait SerializesFolderData
{
    protected function serializeVisibility(FolderVisibility $visibility): string
    {
        return $visibility->value;
    }

    /**
     * @param  EloquentCollection<int, FolderAuthor>  $authors
     * @return array<int, array<string, mixed>>
     */
    protected function serializeAuthors(EloquentCollection $authors): array
    {
        return $authors->map(fn (FolderAuthor $author): array => [
            'id' => $author->id,
            'name' => $author->name,
            'source_url' => $author->source_url,
            'sort_order' => $author->sort_order,
        ])->values()->all();
    }

    /**
     * @return array{display_name: mixed, avatar_url: mixed}
     */
    protected function serializeOwner(object $user): array
    {
        return [
            'display_name' => $user->display_name,
            'avatar_url' => $user->avatar_url,
        ];
    }

    /**
     * @param  EloquentCollection<int, Variant>  $variants
     * @return array<int, array<string, mixed>>
     */
    protected function serializeVariants(EloquentCollection $variants, bool $withSortOrder = false): array
    {
        return $variants->map(function (Variant $variant) use ($withSortOrder): array {
            $data = [
                'id' => $variant->id,
                'name' => $variant->name,
                'is_default' => $variant->is_default,
                'grid_background_preset' => $variant->grid_background_preset,
            ];

            if ($withSortOrder) {
                $data['sort_order'] = $variant->sort_order;
            }

            return $data;
        })->values()->all();
    }
}
