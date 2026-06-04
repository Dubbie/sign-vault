<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sign\DeleteSignsRequest;
use App\Http\Requests\Sign\MoveSignsRequest;
use App\Http\Requests\Sign\StoreSignRequest;
use App\Http\Requests\Variant\ChangeSignVariantRequest;
use App\Http\Resources\SignResource;
use App\Models\Folder;
use App\Models\Sign;
use App\Models\Variant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        $perPage = min((int) $request->query('per_page', 10), 100);
        $defaultVariantId = $folder->defaultVariant?->id;

        $query = $folder->signs()->orderBy('sort_key');

        if ($variantId = $request->integer('variant_id')) {
            $query->where('variant_id', $variantId);
        } elseif ($defaultVariantId !== null) {
            $query->where('variant_id', $defaultVariantId);
        }

        if ($columnRatio = $request->integer('column_ratio')) {
            $query->where('column_ratio', $columnRatio);
        }

        return SignResource::collection($query->paginate($perPage))->response();
    }

    public function store(StoreSignRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();
        $files = $validated['files'];
        $disk = config('filesystems.default');

        $variantId = $validated['variant_id'] ?? $folder->defaultVariant?->id;

        Log::info('Sign bulk upload started.', [
            'user_id' => $request->user()->id,
            'folder_id' => $folder->id,
            'folder_slug' => $folder->slug,
            'variant_id' => $variantId,
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
                'variant_id' => $variantId,
                'file_name' => $file->getClientOriginalName(),
                'derived_name' => $name,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);

            $existingSign = $this->existingSignFor($request->user()->id, $folder->id, $variantId, $name);
            $storageKey = $this->storageKeyFor($request->user()->id, $folder->id, $variantId, $name, $file);
            [$width, $height] = $this->imageDimensions($file);
            $publicUrl = Storage::disk($disk)->url($storageKey);

            $storedStorageKey = $this->storeFile($disk, $storageKey, $file);

            $sign = $existingSign ?? $request->user()->signs()->make([
                'folder_id' => $folder->id,
                'variant_id' => $variantId,
                'name' => $name,
            ]);

            $oldStorageKey = $sign->exists ? $sign->storage_key : null;

            $sign->fill([
                'storage_disk' => $disk,
                'storage_key' => $storedStorageKey,
                'public_url' => $publicUrl,
                'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                'size_bytes' => $file->getSize() ?? 0,
                'width' => $width,
                'height' => $height,
                'column_ratio' => $this->columnRatioFor($width, $height),
                'sort_key' => $this->naturalSortKey($name),
            ]);

            $sign->save();

            if ($oldStorageKey !== null && $oldStorageKey !== $storedStorageKey) {
                Storage::disk($disk)->delete($oldStorageKey);
            }

            $signs[] = $sign->refresh();
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

    public function destroy(DeleteSignsRequest $request): JsonResponse|Response
    {
        $ids = $request->validated('ids');

        $signs = Sign::whereIn('id', $ids)
            ->where('user_id', $request->user()->id)
            ->get();

        if ($signs->isEmpty()) {
            return response()->json(['message' => 'No signs found.'], 404);
        }

        foreach ($signs as $sign) {
            Storage::disk($sign->storage_disk)->delete($sign->storage_key);
            $sign->delete();
        }

        return response()->noContent();
    }

    public function move(MoveSignsRequest $request): JsonResponse
    {
        $ids = $request->validated('ids');
        $targetFolderId = (int) $request->validated('folder_id');

        $targetFolder = Folder::findOrFail($targetFolderId);
        $targetDefaultVariantId = $targetFolder->defaultVariant?->id;

        $updated = Sign::whereIn('id', $ids)
            ->where('user_id', $request->user()->id)
            ->where('folder_id', '!=', $targetFolderId)
            ->update([
                'folder_id' => $targetFolderId,
                'variant_id' => $targetDefaultVariantId,
            ]);

        return response()->json([
            'message' => "{$updated} sign(s) moved successfully.",
            'moved_count' => $updated,
        ]);
    }

    public function changeVariant(ChangeSignVariantRequest $request): JsonResponse
    {
        $ids = $request->validated('ids');
        $variantId = (int) $request->validated('variant_id');

        $variant = Variant::findOrFail($variantId);

        $updated = Sign::whereIn('id', $ids)
            ->where('user_id', $request->user()->id)
            ->where('folder_id', $variant->folder_id)
            ->update(['variant_id' => $variantId]);

        return response()->json([
            'message' => "{$updated} sign(s) variant changed.",
            'changed_count' => $updated,
        ]);
    }

    private function signNameFor(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $derivedName = trim((string) $originalName);

        return $derivedName !== '' ? $derivedName : 'sign';
    }

    private function existingSignFor(int $userId, int $folderId, ?int $variantId, string $name): ?Sign
    {
        $query = Sign::query()
            ->where('user_id', $userId)
            ->where('folder_id', $folderId)
            ->where('name', $name);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }

        return $query->first();
    }

    private function storageKeyFor(
        int $userId,
        int $folderId,
        ?int $variantId,
        string $name,
        UploadedFile $file
    ): string {
        $variant = $variantId !== null ? "/{$variantId}" : '';
        $directory = sprintf('signs/%d/%d%s', $userId, $folderId, $variant);
        $filename = sprintf(
            '%s.%s',
            Str::slug($name) ?: 'sign',
            $file->extension() ?: 'bin'
        );

        return $directory.'/'.$filename;
    }

    private function storeFile(
        string $disk,
        string $storageKey,
        UploadedFile $file
    ): string {
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

        Log::info('Sign upload stored.', [
            'disk' => $disk,
            'storage_key' => $result,
        ]);

        return $result;
    }

    private function naturalSortKey(string $name): string
    {
        $lower = mb_strtolower($name);

        return preg_replace_callback('/\d+/', function (array $matches): string {
            return str_pad($matches[0], 10, '0', STR_PAD_LEFT);
        }, $lower) ?? $lower;
    }

    private function columnRatioFor(?int $width, ?int $height): int
    {
        if (! $width || ! $height) {
            return 1;
        }

        $ratio = $width / $height;
        $columns = [6, 4, 2, 1];
        $closest = $columns[0];
        $minDiff = abs($ratio - $closest);

        foreach ($columns as $col) {
            $diff = abs($ratio - $col);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $col;
            }
        }

        return $closest;
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
