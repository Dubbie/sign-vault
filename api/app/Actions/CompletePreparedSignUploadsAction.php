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
use Illuminate\Support\Facades\DB;
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
        $uploads = $this->preparedUploads->findMany($intentIds);

        foreach ($intentIds as $intentId) {
            $upload = $uploads[$intentId] ?? null;

            if ($upload === null) {
                throw ValidationException::withMessages([
                    'intent_ids' => ['One or more upload intents are missing or expired.'],
                ]);
            }

            $this->ensureUploadMatchesRequest($upload, $user, $folder, $variantId);
        }

        $names = array_unique(array_map(fn (PreparedSignUpload $u) => $u->name, array_values($uploads)));
        $existingMap = $this->loadExistingSigns($user->id, $folder->id, $variantId, $names);

        $signs = collect();
        $thumbnailSignIds = [];
        $oldFilesToDelete = [];

        DB::transaction(function () use ($user, $folder, $uploads, $existingMap, &$signs, &$thumbnailSignIds, &$oldFilesToDelete): void {
            foreach ($uploads as $upload) {
                $existing = $existingMap->get($this->existingKey($upload->name, $upload->width, $upload->height));

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
                    $oldFilesToDelete[] = [$oldDisk, $oldStorageKey];
                }

                if ($oldThumbnailKey !== null) {
                    $oldFilesToDelete[] = [$oldDisk, $oldThumbnailKey];
                }

                if ($shouldQueueThumbnail) {
                    $thumbnailSignIds[] = $sign->id;
                }

                $signs->push($sign);
            }
        });

        foreach ($oldFilesToDelete as [$disk, $key]) {
            $this->signStorage->delete($disk, $key);
        }

        foreach ($thumbnailSignIds as $signId) {
            GenerateSignThumbnail::dispatch($signId);
        }

        $this->preparedUploads->forgetMany(array_keys($uploads));

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

    /**
     * @param  list<string>  $names
     * @return Collection<string, Sign>
     */
    private function loadExistingSigns(int $userId, int $folderId, ?int $variantId, array $names): Collection
    {
        return Sign::where('folder_id', $folderId)
            ->where('user_id', $userId)
            ->where(function ($q) use ($variantId): void {
                $variantId !== null
                    ? $q->where('variant_id', $variantId)
                    : $q->whereNull('variant_id');
            })
            ->whereIn('name', $names)
            ->get()
            ->keyBy(fn (Sign $sign) => $this->existingKey($sign->name, $sign->width, $sign->height));
    }

    private function existingKey(string $name, ?int $width, ?int $height): string
    {
        return $name.'|'.($width ?? 'null').'|'.($height ?? 'null');
    }
}
