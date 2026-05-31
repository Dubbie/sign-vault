<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sign\StoreSignRequest;
use App\Http\Resources\SignResource;
use App\Models\Folder;
use App\Models\Sign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SignController extends Controller
{
    public function index(Request $request, Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $signs = $folder->signs()
            ->latest()
            ->get();

        return SignResource::collection($signs)->response();
    }

    public function store(StoreSignRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();
        $files = $validated['files'];
        $disk = config('filesystems.default');
        Log::info('Sign bulk upload started.', [
            'user_id' => $request->user()->id,
            'folder_id' => $folder->id,
            'folder_slug' => $folder->slug,
            'disk' => $disk,
            'file_count' => count($files),
        ]);

        $signs = [];

        foreach ($files as $file) {
            $name = $this->signNameFor($file);
            Log::info('Sign upload started.', [
                'user_id' => $request->user()->id,
                'folder_id' => $folder->id,
                'folder_slug' => $folder->slug,
                'disk' => $disk,
                'file_name' => $file->getClientOriginalName(),
                'derived_name' => $name,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);

            $storageKey = $this->storeFile($disk, $request->user()->id, $folder->slug, $name, $file);
            [$width, $height] = $this->imageDimensions($file);

            $signs[] = $request->user()->signs()->create([
                'folder_id' => $folder->id,
                'name' => $name,
                'storage_disk' => $disk,
                'storage_key' => $storageKey,
                'public_url' => Storage::disk($disk)->url($storageKey),
                'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                'size_bytes' => $file->getSize() ?? 0,
                'width' => $width,
                'height' => $height,
            ]);
        }

        return response()->json([
            'signs' => SignResource::collection(collect($signs)),
        ], 201);
    }

    public function show(Sign $sign): JsonResponse
    {
        $this->authorize('view', $sign);

        return (new SignResource($sign))->response();
    }

    public function destroy(Sign $sign): JsonResponse
    {
        $this->authorize('delete', $sign);

        Storage::disk($sign->storage_disk)->delete($sign->storage_key);
        $sign->delete();

        return response()->json([
            'message' => 'Sign deleted.',
        ]);
    }

    private function signNameFor(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $derivedName = trim((string) $originalName);

        return $derivedName !== '' ? $derivedName : 'sign';
    }

    private function storeFile(
        string $disk,
        int $userId,
        string $folderSlug,
        string $name,
        UploadedFile $file
    ): string {
        $directory = sprintf('signs/%d/%s', $userId, $folderSlug);
        $filename = sprintf(
            '%s-%s.%s',
            Str::slug($name) ?: 'sign',
            Str::lower(Str::random(6)),
            $file->extension() ?: 'bin'
        );

        try {
            $storageKey = Storage::disk($disk)->putFileAs(
                $directory,
                $file,
                $filename,
                ['visibility' => 'public']
            );
        } catch (Throwable $throwable) {
            Log::error('Sign upload failed.', [
                'disk' => $disk,
                'directory' => $directory,
                'filename' => $filename,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        if ($storageKey === false) {
            Log::error('Sign upload failed.', [
                'disk' => $disk,
                'directory' => $directory,
                'filename' => $filename,
                'reason' => 'filesystem returned false',
            ]);

            throw new RuntimeException('Failed to store sign upload.');
        }

        Log::info('Sign upload stored.', [
            'disk' => $disk,
            'storage_key' => $storageKey,
            'directory' => $directory,
            'filename' => $filename,
        ]);

        return $storageKey;
    }

    /**
     * @return array{0:int|null,1:int|null}
     */
    private function imageDimensions(UploadedFile $file): array
    {
        $dimensions = @getimagesize($file->getRealPath());

        if ($dimensions === false) {
            return [null, null];
        }

        return [
            isset($dimensions[0]) ? (int) $dimensions[0] : null,
            isset($dimensions[1]) ? (int) $dimensions[1] : null,
        ];
    }
}
