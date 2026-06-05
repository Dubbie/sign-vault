<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\Sign;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AdminContentController extends Controller
{
    public function deleteFolder(Folder $folder): JsonResponse
    {
        foreach ($folder->signs as $sign) {
            Storage::disk($sign->storage_disk)->delete($sign->storage_key);
            $sign->delete();
        }

        $folder->delete();

        return response()->json(['message' => 'Folder and its signs have been deleted.']);
    }

    public function deleteSign(Sign $sign): JsonResponse
    {
        Storage::disk($sign->storage_disk)->delete($sign->storage_key);
        $sign->delete();

        return response()->json(['message' => 'Sign has been deleted.']);
    }
}
