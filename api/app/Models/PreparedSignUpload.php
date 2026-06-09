<?php

namespace App\Models;

class PreparedSignUpload
{
    /**
     * @param  array<string, string>  $uploadHeaders
     */
    public function __construct(
        public readonly string $id,
        public readonly int $userId,
        public readonly int $folderId,
        public readonly ?int $variantId,
        public readonly ?string $uploadSessionId,
        public readonly string $originalName,
        public readonly string $name,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly string $disk,
        public readonly string $storageKey,
        public readonly string $publicUrl,
        public readonly string $uploadUrl,
        public readonly array $uploadHeaders,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (string) $payload['id'],
            userId: (int) $payload['user_id'],
            folderId: (int) $payload['folder_id'],
            variantId: isset($payload['variant_id']) ? (int) $payload['variant_id'] : null,
            uploadSessionId: $payload['upload_session_id'] !== null ? (string) $payload['upload_session_id'] : null,
            originalName: (string) $payload['original_name'],
            name: (string) $payload['name'],
            mimeType: (string) $payload['mime_type'],
            sizeBytes: (int) $payload['size_bytes'],
            width: isset($payload['width']) ? (int) $payload['width'] : null,
            height: isset($payload['height']) ? (int) $payload['height'] : null,
            disk: (string) $payload['disk'],
            storageKey: (string) $payload['storage_key'],
            publicUrl: (string) $payload['public_url'],
            uploadUrl: (string) $payload['upload_url'],
            uploadHeaders: is_array($payload['upload_headers'] ?? null) ? $payload['upload_headers'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'folder_id' => $this->folderId,
            'variant_id' => $this->variantId,
            'upload_session_id' => $this->uploadSessionId,
            'original_name' => $this->originalName,
            'name' => $this->name,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
            'width' => $this->width,
            'height' => $this->height,
            'disk' => $this->disk,
            'storage_key' => $this->storageKey,
            'public_url' => $this->publicUrl,
            'upload_url' => $this->uploadUrl,
            'upload_headers' => $this->uploadHeaders,
        ];
    }
}
