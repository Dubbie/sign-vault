<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Sign;
use Illuminate\Support\Facades\Storage;

class SignDeletionService
{
    public function deleteSign(Sign $sign): void
    {
        Storage::disk($sign->storage_disk)->delete($sign->storage_key);

        if ($sign->thumbnail_storage_key !== null) {
            Storage::disk($sign->storage_disk)->delete($sign->thumbnail_storage_key);
        }

        $sign->delete();
    }

    public function deleteFolder(Folder $folder): void
    {
        $folder->loadMissing('signs');

        foreach ($folder->signs as $sign) {
            $this->deleteSign($sign);
        }

        $folder->delete();
    }
}
