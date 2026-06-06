<?php

namespace App\Http\Controllers;

use App\Actions\UploadSignsAction;
use App\Http\Requests\Sign\DeleteSignsRequest;
use App\Http\Requests\Sign\MoveSignsRequest;
use App\Http\Requests\Sign\StoreSignRequest;
use App\Http\Requests\Variant\ChangeSignVariantRequest;
use App\Http\Resources\SignResource;
use App\Models\ActivityLog;
use App\Models\Folder;
use App\Models\Sign;
use App\Models\Variant;
use App\Services\ActivityLogService;
use App\Services\SignDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SignController extends Controller
{
    public function __construct(
        private UploadSignsAction $uploadSigns,
        private SignDeletionService $signDeletion,
        private ActivityLogService $activityLog,
    ) {}

    public function index(Request $request, Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $perPage          = min((int) $request->query('per_page', 10), 100);
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
        $variantId = $validated['variant_id'] ?? $folder->defaultVariant?->id;

        $signs = $this->uploadSigns->handle(
            $request->user(),
            $folder,
            $validated['files'],
            $variantId,
        );

        $this->activityLog->log(ActivityLog::SIGNS_UPLOADED, $request->user()->id, [
            'subject_folder_id' => $folder->id,
            'metadata'          => ['folder_id' => $folder->id, 'folder_name' => $folder->name, 'count' => count($signs)],
            'ip'                => $request->ip(),
        ]);

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
        $ids   = $request->validated('ids');
        $signs = Sign::whereIn('id', $ids)->where('user_id', $request->user()->id)->with('folder:id,name')->get();

        if ($signs->isEmpty()) {
            return response()->json(['message' => 'No signs found.'], 404);
        }

        $folderId   = $signs->first()->folder_id;
        $folderName = $signs->first()->folder?->name;

        foreach ($signs as $sign) {
            $this->signDeletion->deleteSign($sign);
        }

        $this->activityLog->log(ActivityLog::SIGNS_DELETED, $request->user()->id, [
            'subject_folder_id' => $folderId,
            'metadata'          => ['folder_id' => $folderId, 'folder_name' => $folderName, 'count' => $signs->count()],
            'ip'                => $request->ip(),
        ]);

        return response()->noContent();
    }

    public function move(MoveSignsRequest $request): JsonResponse
    {
        $ids            = $request->validated('ids');
        $targetFolderId = (int) $request->validated('folder_id');

        $targetFolder           = Folder::findOrFail($targetFolderId);
        $targetDefaultVariantId = $targetFolder->defaultVariant?->id;

        $updated = Sign::whereIn('id', $ids)
            ->where('user_id', $request->user()->id)
            ->where('folder_id', '!=', $targetFolderId)
            ->update([
                'folder_id'  => $targetFolderId,
                'variant_id' => $targetDefaultVariantId,
            ]);

        return response()->json([
            'message'     => "{$updated} sign(s) moved successfully.",
            'moved_count' => $updated,
        ]);
    }

    public function changeVariant(ChangeSignVariantRequest $request): JsonResponse
    {
        $ids       = $request->validated('ids');
        $variantId = (int) $request->validated('variant_id');

        $variant = Variant::findOrFail($variantId);

        $updated = Sign::whereIn('id', $ids)
            ->where('user_id', $request->user()->id)
            ->where('folder_id', $variant->folder_id)
            ->update(['variant_id' => $variantId]);

        return response()->json([
            'message'       => "{$updated} sign(s) variant changed.",
            'changed_count' => $updated,
        ]);
    }
}
