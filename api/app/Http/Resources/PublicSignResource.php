<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicSignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'variant_id' => $this->variant_id,
            'public_url' => $this->public_url,
            'thumbnail_url' => $this->thumbnail_url,
            'mime_type' => $this->mime_type,
            'width' => $this->width,
            'height' => $this->height,
            'column_ratio' => $this->column_ratio,
        ];
    }
}
