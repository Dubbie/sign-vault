<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\Sign;
use App\Services\SignDeletionService;
use Illuminate\Http\JsonResponse;

class AdminContentController extends Controller
{
    public function __construct(private SignDeletionService $signDeletion) {}

    public function deleteFolder(Folder $folder): JsonResponse
    {
        $this->signDeletion->deleteFolder($folder);

        return response()->json(['message' => 'Folder and its signs have been deleted.']);
    }

    public function deleteSign(Sign $sign): JsonResponse
    {
        $this->signDeletion->deleteSign($sign);

        return response()->json(['message' => 'Sign has been deleted.']);
    }
}
