<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\SerializesFolderData;
use App\Services\FolderPreviewService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrowseFolderResource extends JsonResource
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
            'signs_count' => $this->signs_count,
            'variants_count' => $this->variants_count,
            'authors' => $this->whenLoaded('authors', fn (): array => $this->serializeAuthors($this->authors), []),
            'owner' => $this->serializeOwner($this->user),
            'preview_signs' => $this->selectPreviewSigns(),
            'preview_grid_background_preset' => $this->defaultVariant?->grid_background_preset,
            'votes_count' => (int) ($this->votes_count ?? 0),
            'user_has_voted' => (bool) ($this->user_has_voted ?? false),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function selectPreviewSigns(): array
    {
        $folderSigns = $this->previewSigns ?? collect();

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
                $selected[] = $this->serializePreviewSign($sign);
            }
        }

        return $selected;
    }

    private function categorizeAspect(?int $width, ?int $height): string
    {
        if (! $width || ! $height) {
            return 'unknown';
        }

        $ratio = $width / $height;

        if ($ratio < FolderPreviewService::ASPECT_1_1_MAX) {
            return '1:1';
        }

        if ($ratio < FolderPreviewService::ASPECT_2_1_MAX) {
            return '2:1';
        }

        if ($ratio < FolderPreviewService::ASPECT_4_1_MAX) {
            return '4:1';
        }

        return 'wide';
    }

    private function serializePreviewSign(object $sign): array
    {
        return (new PublicSignResource($sign))->toArray(request());
    }
}
