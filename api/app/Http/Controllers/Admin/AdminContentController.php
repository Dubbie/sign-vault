<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Folder;
use App\Models\Sign;
use App\Services\ActivityLogService;
use App\Services\SignDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminContentController extends Controller
{
    public function __construct(
        private SignDeletionService $signDeletion,
        private ActivityLogService $activityLog,
    ) {}

    public function deleteFolder(Request $request, Folder $folder): JsonResponse
    {
        $folder->load('user:id,display_name');

        $folderId   = $folder->id;
        $folderName = $folder->name;
        $ownerId    = $folder->user_id;
        $ownerName  = $folder->user?->display_name;

        $this->signDeletion->deleteFolder($folder);

        $this->activityLog->log(ActivityLog::ADMIN_FOLDER_DELETED, $request->user()->id, [
            'subject_folder_id' => $folderId,
            'metadata'          => ['folder_name' => $folderName, 'owner_id' => $ownerId, 'owner_name' => $ownerName],
            'ip'                => $request->ip(),
        ]);

        return response()->json(['message' => 'Folder and its signs have been deleted.']);
    }

    public function deleteSign(Request $request, Sign $sign): JsonResponse
    {
        $sign->load('user:id,display_name');

        $signId    = $sign->id;
        $signName  = $sign->name;
        $folderId  = $sign->folder_id;
        $ownerId   = $sign->user_id;
        $ownerName = $sign->user?->display_name;

        $this->signDeletion->deleteSign($sign);

        $this->activityLog->log(ActivityLog::ADMIN_SIGN_DELETED, $request->user()->id, [
            'subject_sign_id' => $signId,
            'metadata'        => ['sign_name' => $signName, 'folder_id' => $folderId, 'owner_id' => $ownerId, 'owner_name' => $ownerName],
            'ip'              => $request->ip(),
        ]);

        return response()->json(['message' => 'Sign has been deleted.']);
    }
}
