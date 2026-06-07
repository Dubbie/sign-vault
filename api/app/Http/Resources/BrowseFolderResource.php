<?php

namespace App\Http\Resources;

use App\Enums\FolderVisibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrowseFolderResource extends JsonResource
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
            'signs_count' => $this->signs_count,
            'variants_count' => $this->variants_count,
            'attribution_name' => $this->attribution_name,
            'attribution_source_url' => $this->attribution_source_url,
            'owner' => [
                'display_name' => $this->user->display_name,
                'avatar_url'   => $this->user->avatar_url,
            ],
            'preview_signs' => $this->selectPreviewSigns(),
            'preview_grid_background_preset' => $this->defaultVariant()?->grid_background_preset,
            'votes_count' => (int) ($this->votes_count ?? 0),
            'user_has_voted' => ($user = auth('sanctum')->user())
                ? $this->votes->contains('user_id', $user->id)
                : false,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function selectPreviewSigns(): array
    {
        $defaultVariantId = $this->defaultVariant()?->id;

        $folderSigns = $this->signs->filter(function ($sign) use ($defaultVariantId): bool {
            if ($defaultVariantId === null) {
                return $sign->variant_id === null;
            }

            return $sign->variant_id === $defaultVariantId;
        });

        $groups = ['1:1' => [], '2:1' => [], '4:1' => [], 'wide' => [], 'unknown' => []];

        foreach ($folderSigns as $sign) {
            $category = $this->categorizeAspect($sign->width, $sign->height);

            if (isset($groups[$category])) {
                $groups[$category][] = $sign;
            }
        }

        $selected = [];

        foreach (['1:1', '2:1', '4:1', 'wide', 'unknown'] as $category) {
            $take = min(6, count($groups[$category]));

            for ($i = 0; $i < $take; $i++) {
                $sign = $groups[$category][$i];
                $selected[] = [
                    'id' => $sign->id,
                    'name' => $sign->name,
                    'public_url' => $sign->public_url,
                    'mime_type' => $sign->mime_type,
                    'width' => $sign->width,
                    'height' => $sign->height,
                    'column_ratio' => $sign->column_ratio,
                ];
            }
        }

        return $selected;
    }

    private function defaultVariant()
    {
        return $this->variants->firstWhere('is_default', true);
    }

    private function categorizeAspect(?int $width, ?int $height): string
    {
        if (! $width || ! $height) {
            return 'unknown';
        }

        $ratio = $width / $height;

        if ($ratio < 1.5) {
            return '1:1';
        }

        if ($ratio < 3) {
            return '2:1';
        }

        if ($ratio < 5) {
            return '4:1';
        }

        return 'wide';
    }
}
