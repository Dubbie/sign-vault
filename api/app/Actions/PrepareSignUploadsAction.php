<?php

namespace App\Actions;

use App\Models\Folder;
use App\Models\PreparedSignUpload;
use App\Models\User;
use App\Services\PreparedSignUploadService;
use App\Services\SignRecordService;
use App\Services\SignStorageService;

class PrepareSignUploadsAction
{
    public function __construct(
        private PreparedSignUploadService $preparedUploads,
        private SignRecordService $signRecord,
        private SignStorageService $signStorage,
    ) {}

    /**
     * @param  list<array{original_name:string,mime_type:string,size_bytes:int,width?:int|null,height?:int|null}>  $files
     * @return list<PreparedSignUpload>
     */
    public function handle(User $user, Folder $folder, array $files, ?int $variantId, ?string $uploadSessionId): array
    {
        $disk = config('filesystems.default');

        return array_map(function (array $file) use ($user, $folder, $variantId, $uploadSessionId, $disk): PreparedSignUpload {
            $name = $this->signRecord->nameForOriginal($file['original_name']);
            $width = isset($file['width']) ? (int) $file['width'] : null;
            $height = isset($file['height']) ? (int) $file['height'] : null;
            $storageKey = $this->signStorage->keyForMetadata(
                $user->id,
                $folder->id,
                $variantId,
                $name,
                (string) pathinfo($file['original_name'], PATHINFO_EXTENSION),
                $width,
                $height,
            );
            $temporaryUpload = $this->signStorage->temporaryUpload($disk, $storageKey, $file['mime_type']);

            $upload = new PreparedSignUpload(
                id: $this->preparedUploads->makeId(),
                userId: $user->id,
                folderId: $folder->id,
                variantId: $variantId,
                uploadSessionId: $uploadSessionId,
                originalName: $file['original_name'],
                name: $name,
                mimeType: $file['mime_type'],
                sizeBytes: (int) $file['size_bytes'],
                width: $width,
                height: $height,
                disk: $disk,
                storageKey: $storageKey,
                publicUrl: $this->signStorage->url($disk, $storageKey),
                uploadUrl: $temporaryUpload['url'],
                uploadHeaders: $temporaryUpload['headers'],
            );

            $this->preparedUploads->store($upload);

            return $upload;
        }, $files);
    }
}
