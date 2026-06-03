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
            'owner' => [
                'discord_username' => $this->user->discord_username,
                'discord_global_name' => $this->user->discord_global_name,
                'discord_avatar' => $this->user->discord_avatar,
            ],
            'preview_signs' => $this->selectPreviewSigns(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function selectPreviewSigns(): array
    {
        $groups = ['1:1' => [], '2:1' => [], '4:1' => [], 'wide' => [], 'unknown' => []];

        foreach ($this->signs as $sign) {
            $category = $this->categorizeAspect($sign->width, $sign->height);

            if (isset($groups[$category])) {
                $groups[$category][] = $sign;
            }
        }

        $selected = [];

        foreach (['1:1', '2:1', '4:1', 'wide', 'unknown'] as $category) {
            $take = min(3, count($groups[$category]));

            for ($i = 0; $i < $take; $i++) {
                $sign = $groups[$category][$i];
                $selected[] = [
                    'id' => $sign->id,
                    'name' => $sign->name,
                    'public_url' => $sign->public_url,
                    'width' => $sign->width,
                    'height' => $sign->height,
                ];
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
