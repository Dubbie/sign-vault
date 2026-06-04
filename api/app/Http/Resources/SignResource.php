<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folder_id' => $this->folder_id,
            'variant_id' => $this->variant_id,
            'name' => $this->name,
            'public_url' => $this->public_url,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'width' => $this->width,
            'height' => $this->height,
            'column_ratio' => $this->column_ratio,
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
