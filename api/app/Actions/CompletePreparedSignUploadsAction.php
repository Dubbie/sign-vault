<?php

namespace App\Actions;

use App\Jobs\GenerateSignThumbnail;
use App\Models\Folder;
use App\Models\PreparedSignUpload;
use App\Models\Sign;
use App\Models\User;
use App\Services\PreparedSignUploadService;
use App\Services\SignRecordService;
use App\Services\SignStorageService;
use App\Services\SignThumbnailService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CompletePreparedSignUploadsAction
{
    public function __construct(
        private PreparedSignUploadService $preparedUploads,
        private SignRecordService $signRecord,
        private SignStorageService $signStorage,
        private SignThumbnailService $signThumbnail,
    ) {}

    /**
     * @param  list<string>  $intentIds
     * @return Collection<int, Sign>
     */
    public function handle(User $user, Folder $folder, array $intentIds, ?int $variantId): Collection
    {
        $signs = collect();

        foreach ($intentIds as $intentId) {
            $upload = $this->preparedUploads->find($intentId);

            if ($upload === null) {
                throw ValidationException::withMessages([
                    'intent_ids' => ['One or more upload intents are missing or expired.'],
                ]);
            }

            $this->ensureUploadMatchesRequest($upload, $user, $folder, $variantId);

            $signs->push($this->upsertSign($user, $folder, $upload));
            $this->preparedUploads->forget($upload->id);
        }

        return $signs;
    }

    private function ensureUploadMatchesRequest(PreparedSignUpload $upload, User $user, Folder $folder, ?int $variantId): void
    {
        if ($upload->userId !== $user->id || $upload->folderId !== $folder->id || $upload->variantId !== $variantId) {
            throw ValidationException::withMessages([
                'intent_ids' => ['One or more upload intents do not match this folder or variant.'],
            ]);
        }
    }

    private function upsertSign(User $user, Folder $folder, PreparedSignUpload $upload): Sign
    {
        $existing = $this->signRecord->findExisting(
            $user->id,
            $folder->id,
            $upload->variantId,
            $upload->name,
            $upload->width,
            $upload->height,
        );

        $sign = $existing ?? $user->signs()->make([
            'folder_id' => $folder->id,
            'variant_id' => $upload->variantId,
            'name' => $upload->name,
        ]);

        $oldStorageKey = $sign->exists ? $sign->storage_key : null;
        $oldThumbnailKey = $sign->exists ? $sign->thumbnail_storage_key : null;
        $oldDisk = $sign->exists ? $sign->storage_disk : $upload->disk;
        $shouldQueueThumbnail = $this->signThumbnail->supportsMimeType($upload->mimeType);

        $sign->fill([
            'storage_disk' => $upload->disk,
            'storage_key' => $upload->storageKey,
            'public_url' => $upload->publicUrl,
            'thumbnail_url' => null,
            'thumbnail_storage_key' => null,
            'thumbnail_status' => $shouldQueueThumbnail ? Sign::THUMBNAIL_STATUS_PENDING : Sign::THUMBNAIL_STATUS_READY,
            'mime_type' => $upload->mimeType,
            'size_bytes' => $upload->sizeBytes,
            'width' => $upload->width,
            'height' => $upload->height,
            'column_ratio' => $this->signRecord->columnRatioFor($upload->width, $upload->height),
            'sort_key' => $this->signRecord->naturalSortKey($upload->name),
        ]);
        $sign->save();

        if ($oldStorageKey !== null && $oldStorageKey !== $upload->storageKey) {
            $this->signStorage->delete($oldDisk, $oldStorageKey);
        }

        if ($oldThumbnailKey !== null) {
            $this->signStorage->delete($oldDisk, $oldThumbnailKey);
        }

        if ($shouldQueueThumbnail) {
            GenerateSignThumbnail::dispatch($sign->id);
        }

        return $sign;
    }
}
