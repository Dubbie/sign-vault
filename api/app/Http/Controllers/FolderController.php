<?php

namespace App\Http\Controllers;

use App\Http\Requests\Folder\StoreFolderRequest;
use App\Http\Requests\Folder\UpdateFolderRequest;
use App\Http\Resources\FolderResource;
use App\Models\ActivityLog;
use App\Models\Folder;
use App\Services\ActivityLogService;
use App\Services\FolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function __construct(
        private FolderService $folderService,
        private ActivityLogService $activityLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $folders = $request->user()
            ->folders()
            ->with('variants')
            ->latest()
            ->get();

        return FolderResource::collection($folders)->response();
    }

    public function store(StoreFolderRequest $request): JsonResponse
    {
        $this->authorize('create', Folder::class);

        $validated = $request->validated();

        $folder = $request->user()->folders()->create([
            'name' => $validated['name'],
            'slug' => Folder::generateSlugFor($request->user(), $validated['name']),
            'public_slug' => Folder::generatePublicSlugFor($validated['name']),
            'visibility' => $validated['visibility'],
            'password_hash' => $this->folderService->hashPassword($validated),
            'attribution_name' => $validated['attribution_name'] ?? null,
            'attribution_source_url' => $validated['attribution_source_url'] ?? null,
        ]);

        $this->activityLog->log(ActivityLog::FOLDER_CREATED, $request->user()->id, [
            'subject_folder_id' => $folder->id,
            'metadata' => ['folder_name' => $folder->name, 'visibility' => $folder->visibility],
            'ip' => $request->ip(),
        ]);

        return (new FolderResource($folder->refresh()))->response()->setStatusCode(201);
    }

    public function show(Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $folder->load('variants');

        return (new FolderResource($folder))->response();
    }

    public function update(UpdateFolderRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();

        $oldVisibility = $folder->visibility;

        // Resolve public slug BEFORE fill() changes the current visibility on the model.
        $publicSlug = $this->folderService->resolvePublicSlug($folder, $validated);

        $folder->fill([
            'name' => $validated['name'],
            'slug' => Folder::generateSlugFor($request->user(), $validated['name'], $folder->id),
            'visibility' => $validated['visibility'],
            'attribution_name' => $validated['attribution_name'] ?? null,
            'attribution_source_url' => $validated['attribution_source_url'] ?? null,
        ]);

        if ($publicSlug !== null) {
            $folder->public_slug = $publicSlug;
        }

        $folder->password_hash = $this->folderService->hashPassword($validated);
        $folder->save();

        if ($oldVisibility !== $folder->visibility) {
            $this->activityLog->log(ActivityLog::FOLDER_VISIBILITY, $request->user()->id, [
                'subject_folder_id' => $folder->id,
                'metadata' => ['folder_name' => $folder->name, 'from' => $oldVisibility, 'to' => $folder->visibility],
                'ip' => $request->ip(),
            ]);
        }

        return (new FolderResource($folder->refresh()))->response();
    }

    public function destroy(Request $request, Folder $folder): JsonResponse
    {
        $this->authorize('delete', $folder);

        if ($folder->signs()->exists()) {
            return response()->json([
                'message' => 'Folder must be empty before deletion.',
            ], 409);
        }

        $folderName = $folder->name;
        $folderId = $folder->id;
        $folder->delete();

        $this->activityLog->log(ActivityLog::FOLDER_DELETED, $request->user()->id, [
            'subject_folder_id' => $folderId,
            'metadata' => ['folder_name' => $folderName],
            'ip' => $request->ip(),
        ]);

        return response()->json(['message' => 'Folder deleted.']);
    }
}
