<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SignStorageService
{
    public function keyForMetadata(
        int $userId,
        int $folderId,
        ?int $variantId,
        string $name,
        string $extension,
        ?int $width,
        ?int $height
    ): string {
        $variantSegment = $variantId !== null ? "/{$variantId}" : '';
        $directory = sprintf('signs/%d/%d%s', $userId, $folderId, $variantSegment);
        $dimensionSuffix = $width !== null && $height !== null ? "-{$width}x{$height}" : '';
        $normalizedExtension = $extension !== '' ? $extension : 'bin';
        $filename = sprintf(
            '%s%s.%s',
            Str::slug($name) ?: 'sign',
            $dimensionSuffix,
            $normalizedExtension
        );

        return $directory.'/'.$filename;
    }

    public function keyFor(
        int $userId,
        int $folderId,
        ?int $variantId,
        string $name,
        UploadedFile $file,
        ?int $width,
        ?int $height
    ): string {
        return $this->keyForMetadata(
            $userId,
            $folderId,
            $variantId,
            $name,
            $file->extension() ?: 'bin',
            $width,
            $height,
        );
    }

    public function thumbnailKeyFor(
        int $userId,
        int $folderId,
        ?int $variantId,
        string $name,
        ?int $width,
        ?int $height
    ): string {
        $variantSegment = $variantId !== null ? "/{$variantId}" : '';
        $directory = sprintf('signs/%d/%d%s', $userId, $folderId, $variantSegment);
        $dimensionSuffix = $width !== null && $height !== null ? "-{$width}x{$height}" : '';
        $filename = sprintf(
            '%s%s-thumb.webp',
            Str::slug($name) ?: 'sign',
            $dimensionSuffix,
        );

        return $directory.'/'.$filename;
    }

    public function store(string $disk, string $storageKey, UploadedFile $file): string
    {
        try {
            $result = $this->internalDisk($disk)->putFileAs(
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

    public function storeThumbnail(string $disk, string $storageKey, string $contents): string
    {
        try {
            $result = $this->internalDisk($disk)->put($storageKey, $contents, ['visibility' => 'public']);
        } catch (Throwable $throwable) {
            Log::error('Sign thumbnail storage failed.', [
                'disk' => $disk,
                'storage_key' => $storageKey,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        if ($result === false) {
            Log::error('Sign thumbnail storage failed.', [
                'disk' => $disk,
                'storage_key' => $storageKey,
                'reason' => 'filesystem returned false',
            ]);

            throw new RuntimeException('Failed to store sign thumbnail.');
        }

        Log::info('Sign thumbnail stored.', ['disk' => $disk, 'storage_key' => $storageKey]);

        return $storageKey;
    }

    /**
     * @return array{url:string,headers:array<string,string>}
     */
    public function temporaryUpload(string $disk, string $storageKey, string $mimeType): array
    {
        /** @var mixed $filesystem */
        $filesystem = Storage::disk($disk);

        if (! method_exists($filesystem, 'temporaryUploadUrl')) {
            throw new RuntimeException('This filesystem does not support direct uploads.');
        }

        /** @var array{url:string,headers:array<string,string>} $upload */
        $upload = $filesystem->temporaryUploadUrl(
            $storageKey,
            now()->addMinutes(15),
            [
                'ContentType' => $mimeType,
            ],
        );

        return [
            'url' => $upload['url'],
            'headers' => $upload['headers'] ?? [],
        ];
    }

    public function url(string $disk, string $storageKey): string
    {
        return Storage::disk($disk)->url($storageKey);
    }

    public function exists(string $disk, string $storageKey): bool
    {
        return $this->internalDisk($disk)->exists($storageKey);
    }

    public function get(string $disk, string $storageKey): string
    {
        return $this->internalDisk($disk)->get($storageKey);
    }

    public function delete(string $disk, string $storageKey): void
    {
        $this->internalDisk($disk)->delete($storageKey);
    }

    private function internalDisk(string $disk): Filesystem
    {
        if (app()->environment('testing')) {
            return Storage::disk($disk);
        }

        $config = config("filesystems.disks.{$disk}");

        if (! is_array($config)) {
            return Storage::disk($disk);
        }

        $internalEndpoint = $config['internal_endpoint'] ?? null;

        if (! is_string($internalEndpoint) || $internalEndpoint === '') {
            return Storage::disk($disk);
        }

        $internalConfig = $config;
        $internalConfig['endpoint'] = $internalEndpoint;

        return Storage::build($internalConfig);
    }
}
