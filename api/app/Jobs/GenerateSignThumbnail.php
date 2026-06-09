<?php

namespace App\Jobs;

use App\Models\Sign;
use App\Services\MediaMetadataExtractor;
use App\Services\SignRecordService;
use App\Services\SignStorageService;
use App\Services\SignThumbnailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GenerateSignThumbnail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $signId,
    ) {
        $this->onQueue('sign-thumbnails');
    }

    public function handle(
        SignStorageService $signStorage,
        SignThumbnailService $signThumbnail,
        MediaMetadataExtractor $mediaMetadata,
        SignRecordService $signRecord,
    ): void {
        $sign = Sign::query()->find($this->signId);

        if ($sign === null || ! $signThumbnail->supportsMimeType($sign->mime_type)) {
            return;
        }

        $contents = $signStorage->get($sign->storage_disk, $sign->storage_key);
        $thumbnail = $signThumbnail->generateFromContents($contents, $sign->mime_type);

        if ($thumbnail === null) {
            throw new RuntimeException('Thumbnail generation returned no contents.');
        }

        [$width, $height] = $mediaMetadata->imageDimensionsFromContents($contents, $sign->mime_type);
        $thumbnailKey = $signStorage->thumbnailKeyFor(
            $sign->user_id,
            $sign->folder_id,
            $sign->variant_id,
            $sign->name,
            $width ?? $sign->width,
            $height ?? $sign->height,
        );
        $oldThumbnailKey = $sign->thumbnail_storage_key;

        $signStorage->storeThumbnail($sign->storage_disk, $thumbnailKey, $thumbnail);

        $sign->fill([
            'thumbnail_url' => $signStorage->url($sign->storage_disk, $thumbnailKey),
            'thumbnail_storage_key' => $thumbnailKey,
            'thumbnail_status' => Sign::THUMBNAIL_STATUS_READY,
            'width' => $width ?? $sign->width,
            'height' => $height ?? $sign->height,
            'column_ratio' => $signRecord->columnRatioFor($width ?? $sign->width, $height ?? $sign->height),
        ]);
        $sign->save();

        if ($oldThumbnailKey !== null && $oldThumbnailKey !== $thumbnailKey) {
            $signStorage->delete($sign->storage_disk, $oldThumbnailKey);
        }
    }

    public function failed(Throwable $throwable): void
    {
        $sign = Sign::query()->find($this->signId);

        if ($sign !== null) {
            $sign->forceFill([
                'thumbnail_status' => Sign::THUMBNAIL_STATUS_FAILED,
            ])->save();
        }

        Log::warning('Queued sign thumbnail generation failed.', [
            'sign_id' => $this->signId,
            'exception' => $throwable::class,
            'message' => $throwable->getMessage(),
        ]);
    }
}
