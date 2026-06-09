<?php

namespace App\Console\Commands;

use App\Models\Sign;
use App\Services\SignStorageService;
use App\Services\SignThumbnailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackfillSignThumbnails extends Command
{
    protected $signature = 'signs:backfill-thumbnails';

    protected $description = 'Generate thumbnails for signs uploaded before thumbnail generation existed';

    public function handle(SignThumbnailService $signThumbnail, SignStorageService $signStorage): int
    {
        $generated = 0;
        $skipped = 0;
        $failed = 0;

        Sign::query()
            ->whereNull('thumbnail_url')
            ->whereIn('mime_type', ['image/png', 'image/jpeg', 'image/webp', 'image/avif'])
            ->chunkById(50, function ($signs) use ($signThumbnail, $signStorage, &$generated, &$skipped, &$failed) {
                foreach ($signs as $sign) {
                    try {
                        $contents = Storage::disk($sign->storage_disk)->get($sign->storage_key);

                        if ($contents === null) {
                            $skipped++;

                            continue;
                        }

                        $thumbnail = $signThumbnail->generateFromContents($contents, $sign->mime_type);

                        if ($thumbnail === null) {
                            $skipped++;

                            continue;
                        }

                        $thumbnailKey = $signStorage->thumbnailKeyFor(
                            $sign->user_id,
                            $sign->folder_id,
                            $sign->variant_id,
                            $sign->name,
                            $sign->width,
                            $sign->height,
                        );

                        $signStorage->storeThumbnail($sign->storage_disk, $thumbnailKey, $thumbnail);

                        $sign->forceFill([
                            'thumbnail_url' => $signStorage->url($sign->storage_disk, $thumbnailKey),
                            'thumbnail_storage_key' => $thumbnailKey,
                        ])->save();

                        $generated++;
                    } catch (Throwable $throwable) {
                        $failed++;

                        Log::warning('Sign thumbnail backfill failed for sign.', [
                            'sign_id' => $sign->id,
                            'exception' => $throwable::class,
                            'message' => $throwable->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Backfill complete. Generated: {$generated}, skipped: {$skipped}, failed: {$failed}.");

        return self::SUCCESS;
    }
}
