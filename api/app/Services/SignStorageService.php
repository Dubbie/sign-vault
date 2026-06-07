<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SignStorageService
{
    public function keyFor(
        int $userId,
        int $folderId,
        ?int $variantId,
        string $name,
        UploadedFile $file,
        ?int $width,
        ?int $height
    ): string {
        $variantSegment = $variantId !== null ? "/{$variantId}" : '';
        $directory = sprintf('signs/%d/%d%s', $userId, $folderId, $variantSegment);
        $dimensionSuffix = $width !== null && $height !== null ? "-{$width}x{$height}" : '';
        $filename = sprintf(
            '%s%s.%s',
            Str::slug($name) ?: 'sign',
            $dimensionSuffix,
            $file->extension() ?: 'bin'
        );

        return $directory.'/'.$filename;
    }

    public function store(string $disk, string $storageKey, UploadedFile $file): string
    {
        try {
            $result = Storage::disk($disk)->putFileAs(
                dirname($storageKey),
                $file,
                basename($storageKey),
                ['visibility' => 'public']
            );
        } catch (Throwable $throwable) {
            Log::error('Sign upload failed.', [
                'disk' => $disk,
                'storage_key' => $storageKey,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        if ($result === false) {
            Log::error('Sign upload failed.', [
                'disk' => $disk,
                'storage_key' => $storageKey,
                'reason' => 'filesystem returned false',
            ]);

            throw new RuntimeException('Failed to store sign upload.');
        }

        Log::info('Sign upload stored.', ['disk' => $disk, 'storage_key' => $result]);

        return $result;
    }

    public function url(string $disk, string $storageKey): string
    {
        return Storage::disk($disk)->url($storageKey);
    }

    public function delete(string $disk, string $storageKey): void
    {
        Storage::disk($disk)->delete($storageKey);
    }
}
