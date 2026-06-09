<?php

namespace App\Http\Controllers;

use App\Actions\CompletePreparedSignUploadsAction;
use App\Actions\PrepareSignUploadsAction;
use App\Http\Requests\Sign\CompletePreparedSignUploadsRequest;
use App\Http\Requests\Sign\PrepareSignUploadsRequest;
use App\Http\Resources\SignResource;
use App\Models\Folder;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;

class SignUploadController extends Controller
{
    public function __construct(
        private PrepareSignUploadsAction $prepareUploads,
        private CompletePreparedSignUploadsAction $completeUploads,
        private ActivityLogService $activityLog,
    ) {}

    public function prepare(PrepareSignUploadsRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();
        $variantId = $validated['variant_id'] ?? $folder->defaultVariant?->id;
        $uploadSessionId = $validated['upload_session_id'] ?? null;
        $uploads = $this->prepareUploads->handle(
            $request->user(),
            $folder,
            $validated['files'],
            $variantId,
            $uploadSessionId,
        );

        return response()->json([
            'uploads' => array_map(static fn ($upload): array => [
                'id' => $upload->id,
                'original_name' => $upload->originalName,
                'storage_key' => $upload->storageKey,
                'public_url' => $upload->publicUrl,
                'upload_url' => $upload->uploadUrl,
                'upload_headers' => $upload->uploadHeaders,
            ], $uploads),
        ]);
    }

    public function complete(CompletePreparedSignUploadsRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();
        $variantId = $validated['variant_id'] ?? $folder->defaultVariant?->id;
        $uploadSessionId = $validated['upload_session_id'] ?? null;
        $signs = $this->completeUploads->handle(
            $request->user(),
            $folder,
            $validated['intent_ids'],
            $variantId,
        );

        $this->activityLog->logUploadedSigns(
            $request->user()->id,
            $folder->id,
            $folder->name,
            $signs->count(),
            $request->ip(),
            $uploadSessionId,
        );

        return response()->json([
            'signs' => SignResource::collection($signs),
        ], 201);
    }
}
