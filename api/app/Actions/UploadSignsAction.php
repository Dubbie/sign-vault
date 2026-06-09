<?php

namespace App\Actions;

use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use App\Services\MediaMetadataExtractor;
use App\Services\SignRecordService;
use App\Services\SignStorageService;
use App\Services\SignThumbnailService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class UploadSignsAction
{
    public function __construct(
        private MediaMetadataExtractor $mediaMetadata,
        private SignRecordService $signRecord,
        private SignStorageService $signStorage,
        private SignThumbnailService $signThumbnail,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return list<Sign>
     */
    public function handle(User $user, Folder $folder, array $files, ?int $variantId): array
    {
        $disk = config('filesystems.default');

        Log::info('Sign bulk upload started.', [
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'folder_slug' => $folder->slug,
            'variant_id' => $variantId,
            'disk' => $disk,
            'file_count' => count($files),
        ]);

        return array_map(
            fn (UploadedFile $file) => $this->uploadSingle($user, $folder, $file, $variantId, $disk),
            $files,
        );
    }

    private function uploadSingle(
        User $user,
        Folder $folder,
        UploadedFile $file,
        ?int $variantId,
        string $disk
    ): Sign {
        $name = $this->signRecord->nameForOriginal($file->getClientOriginalName());

        Log::info('Sign upload started.', [
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'folder_slug' => $folder->slug,
            'variant_id' => $variantId,
            'file_name' => $file->getClientOriginalName(),
            'derived_name' => $name,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        [$width, $height] = $this->mediaMetadata->dimensions($file);

        $existing = $this->signRecord->findExisting($user->id, $folder->id, $variantId, $name, $width, $height);
        $storageKey = $this->signStorage->keyFor($user->id, $folder->id, $variantId, $name, $file, $width, $height);
        $publicUrl = $this->signStorage->url($disk, $storageKey);
        $storedKey = $this->signStorage->store($disk, $storageKey, $file);

        $thumbnailKey = $this->storeThumbnail($user, $folder, $file, $variantId, $name, $width, $height, $disk);

        $sign = $existing ?? $user->signs()->make([
            'folder_id' => $folder->id,
            'variant_id' => $variantId,
            'name' => $name,
        ]);

        $oldStorageKey = $sign->exists ? $sign->storage_key : null;
        $oldThumbnailKey = $sign->exists ? $sign->thumbnail_storage_key : null;

        $sign->fill([
            'storage_disk' => $disk,
            'storage_key' => $storedKey,
            'public_url' => $publicUrl,
            'thumbnail_url' => $thumbnailKey !== null ? $this->signStorage->url($disk, $thumbnailKey) : null,
            'thumbnail_storage_key' => $thumbnailKey,
            'thumbnail_status' => $this->thumbnailStatusFor($file, $thumbnailKey),
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'size_bytes' => $file->getSize() ?? 0,
            'width' => $width,
            'height' => $height,
            'column_ratio' => $this->signRecord->columnRatioFor($width, $height),
            'sort_key' => $this->signRecord->naturalSortKey($name),
        ]);

        $sign->save();

        if ($oldStorageKey !== null && $oldStorageKey !== $storedKey) {
            $this->signStorage->delete($disk, $oldStorageKey);
        }

        if ($oldThumbnailKey !== null && $oldThumbnailKey !== $thumbnailKey) {
            $this->signStorage->delete($disk, $oldThumbnailKey);
        }

        return $sign->refresh();
    }

    private function storeThumbnail(
        User $user,
        Folder $folder,
        UploadedFile $file,
        ?int $variantId,
        string $name,
        ?int $width,
        ?int $height,
        string $disk
    ): ?string {
        $contents = $this->signThumbnail->generate($file);

        if ($contents === null) {
            return null;
        }

        $thumbnailKey = $this->signStorage->thumbnailKeyFor($user->id, $folder->id, $variantId, $name, $width, $height);
        $this->signStorage->storeThumbnail($disk, $thumbnailKey, $contents);

        return $thumbnailKey;
    }

    private function thumbnailStatusFor(UploadedFile $file, ?string $thumbnailKey): string
    {
        if ($thumbnailKey !== null) {
            return Sign::THUMBNAIL_STATUS_READY;
        }

        $mimeType = $file->getMimeType() ?? $file->getClientMimeType();

        if ($this->signThumbnail->supportsMimeType($mimeType)) {
            return Sign::THUMBNAIL_STATUS_FAILED;
        }

        return Sign::THUMBNAIL_STATUS_READY;
    }
}
