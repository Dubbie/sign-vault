<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Throwable;

class SignThumbnailService
{
    /**
     * Preview grids display signs at roughly 68px tall; thumbnails are
     * generated at twice that height to stay crisp on high-DPI screens.
     */
    private const THUMBNAIL_HEIGHT = 128;

    private const WEBP_QUALITY = 80;

    /**
     * Mime types we know how to decode and resize. Videos (and anything
     * else) are skipped — callers should fall back to the original.
     *
     * @var list<string>
     */
    private const SUPPORTED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/avif',
    ];

    public function supportsMimeType(string $mimeType): bool
    {
        return in_array($mimeType, self::SUPPORTED_MIME_TYPES, true);
    }

    public function generate(UploadedFile $file): ?string
    {
        $mimeType = $file->getMimeType() ?? $file->getClientMimeType();
        $path = $file->getRealPath();

        if ($path === false) {
            return null;
        }

        if (! $this->supportsMimeType($mimeType)) {
            return null;
        }

        try {
            $manager = ImageManager::usingDriver(Driver::class);
            $image = $manager->decodePath($path);
            $image->scaleDown(height: self::THUMBNAIL_HEIGHT);

            return (string) $image->encode(new WebpEncoder(quality: self::WEBP_QUALITY));
        } catch (Throwable $throwable) {
            Log::warning('Sign thumbnail generation failed.', [
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $mimeType,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return null;
        }
    }

    public function generateFromContents(string $contents, string $mimeType): ?string
    {
        if (! $this->supportsMimeType($mimeType)) {
            return null;
        }

        try {
            $manager = ImageManager::usingDriver(Driver::class);
            $image = $manager->decode($contents);
            $image->scaleDown(height: self::THUMBNAIL_HEIGHT);

            return (string) $image->encode(new WebpEncoder(quality: self::WEBP_QUALITY));
        } catch (Throwable $throwable) {
            Log::warning('Sign thumbnail generation from contents failed.', [
                'mime_type' => $mimeType,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return null;
        }
    }
}
