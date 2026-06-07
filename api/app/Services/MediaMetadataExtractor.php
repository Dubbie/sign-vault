<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class MediaMetadataExtractor
{
    /**
     * @return array{0:int|null,1:int|null}
     */
    public function dimensions(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType() ?? $file->getClientMimeType();

        if ($mimeType === 'video/webm') {
            return $this->videoDimensions($file);
        }

        $dimensions = @getimagesize($file->getRealPath());

        if ($dimensions === false) {
            return [null, null];
        }

        return [
            isset($dimensions[0]) ? (int) $dimensions[0] : null,
            isset($dimensions[1]) ? (int) $dimensions[1] : null,
        ];
    }

    /**
     * @return array{0:int|null,1:int|null}
     */
    private function videoDimensions(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return [null, null];
        }

        $process = new Process([
            'ffprobe', '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height',
            '-of', 'json',
            $path,
        ]);

        try {
            $process->mustRun();
        } catch (Throwable $throwable) {
            Log::warning('Video metadata probe failed.', [
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return [null, null];
        }

        /** @var array{streams?: list<array{width?: int|float|string|null,height?: int|float|string|null}>}|null $payload */
        $payload = json_decode($process->getOutput(), true);
        $stream = $payload['streams'][0] ?? null;

        if (! is_array($stream)) {
            return [null, null];
        }

        $width = isset($stream['width']) && is_numeric($stream['width']) ? (int) $stream['width'] : null;
        $height = isset($stream['height']) && is_numeric($stream['height']) ? (int) $stream['height'] : null;

        return [$width, $height];
    }
}
