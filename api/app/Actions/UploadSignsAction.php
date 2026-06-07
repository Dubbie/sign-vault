<?php

namespace App\Actions;

use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use App\Services\MediaMetadataExtractor;
use App\Services\SignStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class UploadSignsAction
{
    public function __construct(
        private MediaMetadataExtractor $mediaMetadata,
        private SignStorageService $signStorage,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return list<Sign>
     */
    public function handle(User $user, Folder $folder, array $files, ?int $variantId): array
    {
        $disk = config('filesystems.default');

        Log::info('Sign bulk upload started.', [
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'folder_slug' => $folder->slug,
            'variant_id' => $variantId,
            'disk' => $disk,
            'file_count' => count($files),
        ]);

        return array_map(
            fn (UploadedFile $file) => $this->uploadSingle($user, $folder, $file, $variantId, $disk),
            $files,
        );
    }

    private function uploadSingle(
        User $user,
        Folder $folder,
        UploadedFile $file,
        ?int $variantId,
        string $disk
    ): Sign {
        $name = $this->nameFor($file);

        Log::info('Sign upload started.', [
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'folder_slug' => $folder->slug,
            'variant_id' => $variantId,
            'file_name' => $file->getClientOriginalName(),
            'derived_name' => $name,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        [$width, $height] = $this->mediaMetadata->dimensions($file);

        $existing = $this->findExisting($user->id, $folder->id, $variantId, $name, $width, $height);
        $storageKey = $this->signStorage->keyFor($user->id, $folder->id, $variantId, $name, $file, $width, $height);
        $publicUrl = $this->signStorage->url($disk, $storageKey);
        $storedKey = $this->signStorage->store($disk, $storageKey, $file);

        $sign = $existing ?? $user->signs()->make([
            'folder_id' => $folder->id,
            'variant_id' => $variantId,
            'name' => $name,
        ]);

        $oldStorageKey = $sign->exists ? $sign->storage_key : null;

        $sign->fill([
            'storage_disk' => $disk,
            'storage_key' => $storedKey,
            'public_url' => $publicUrl,
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'size_bytes' => $file->getSize() ?? 0,
            'width' => $width,
            'height' => $height,
            'column_ratio' => $this->columnRatioFor($width, $height),
            'sort_key' => $this->naturalSortKey($name),
        ]);

        $sign->save();

        if ($oldStorageKey !== null && $oldStorageKey !== $storedKey) {
            $this->signStorage->delete($disk, $oldStorageKey);
        }

        return $sign->refresh();
    }

    private function nameFor(UploadedFile $file): string
    {
        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $derived = trim((string) $original);

        return $derived !== '' ? $derived : 'sign';
    }

    private function findExisting(
        int $userId,
        int $folderId,
        ?int $variantId,
        string $name,
        ?int $width,
        ?int $height
    ): ?Sign {
        $query = Sign::query()
            ->where('user_id', $userId)
            ->where('folder_id', $folderId)
            ->where('name', $name);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }

        if ($width !== null && $height !== null) {
            $query->where('width', $width)->where('height', $height);
        } else {
            $query->whereNull('width')->whereNull('height');
        }

        return $query->first();
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

    private function naturalSortKey(string $name): string
    {
        $lower = mb_strtolower($name);

        return preg_replace_callback('/\d+/', function (array $matches): string {
            return str_pad($matches[0], 10, '0', STR_PAD_LEFT);
        }, $lower) ?? $lower;
    }
}
