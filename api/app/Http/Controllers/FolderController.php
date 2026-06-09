<?php

namespace App\Http\Controllers;

use App\Enums\FolderVisibility;
use App\Http\Requests\Folder\StoreFolderRequest;
use App\Http\Requests\Folder\UpdateFolderRequest;
use App\Http\Resources\FolderResource;
use App\Models\ActivityLog;
use App\Models\Folder;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FolderController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $folders = $request->user()
            ->folders()
            ->with(['authors', 'variants'])
            ->latest()
            ->get();

        return FolderResource::collection($folders)->response();
    }

    public function store(StoreFolderRequest $request): JsonResponse
    {
        $this->authorize('create', Folder::class);

        $validated = $request->validated();

        $folder = DB::transaction(function () use ($request, $validated): Folder {
            $folder = $request->user()->folders()->create([
                'name' => $validated['name'],
                'slug' => Folder::generateSlugFor($request->user(), $validated['name']),
                'public_slug' => Folder::generatePublicSlugFor($validated['name']),
                'visibility' => $validated['visibility'],
                'password_hash' => $this->hashPassword($validated),
            ]);

            $this->syncAuthors($folder, $validated['authors'] ?? []);

            return $folder;
        });

        $this->activityLog->log(ActivityLog::FOLDER_CREATED, $request->user()->id, [
            'subject_folder_id' => $folder->id,
            'metadata' => ['folder_name' => $folder->name, 'visibility' => $folder->visibility],
            'ip' => $request->ip(),
        ]);

        return (new FolderResource($folder->fresh(['authors', 'variants'])))->response()->setStatusCode(201);
    }

    public function show(Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $folder->load(['authors', 'variants']);

        return (new FolderResource($folder))->response();
    }

    public function update(UpdateFolderRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();

        $oldVisibility = $folder->visibility;

        // Resolve public slug BEFORE fill() changes the current visibility on the model.
        $publicSlug = $this->resolvePublicSlug($folder, $validated);

        DB::transaction(function () use ($request, $validated, $folder, $publicSlug): void {
            $folder->fill([
                'name' => $validated['name'],
                'slug' => Folder::generateSlugFor($request->user(), $validated['name'], $folder->id),
                'visibility' => $validated['visibility'],
            ]);

            if ($publicSlug !== null) {
                $folder->public_slug = $publicSlug;
            }

            $folder->password_hash = $this->hashPassword($validated);
            $folder->save();

            if (array_key_exists('authors', $validated)) {
                $this->syncAuthors($folder, $validated['authors'] ?? []);
            }
        });

        if ($oldVisibility !== $folder->visibility) {
            $this->activityLog->log(ActivityLog::FOLDER_VISIBILITY, $request->user()->id, [
                'subject_folder_id' => $folder->id,
                'metadata' => ['folder_name' => $folder->name, 'from' => $oldVisibility, 'to' => $folder->visibility],
                'ip' => $request->ip(),
            ]);
        }

        return (new FolderResource($folder->fresh(['authors', 'variants'])))->response();
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

    /**
     * @param  array<int, array{name: string, source_url?: string|null}>  $authors
     */
    private function syncAuthors(Folder $folder, array $authors): void
    {
        $folder->authors()->delete();

        $rows = collect($authors)
            ->map(function (array $author, int $index) use ($folder): array {
                return [
                    'folder_id' => $folder->id,
                    'name' => trim($author['name']),
                    'source_url' => filled($author['source_url'] ?? null) ? $author['source_url'] : null,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->filter(fn (array $author): bool => $author['name'] !== '')
            ->values()
            ->all();

        if ($rows === []) {
            return;
        }

        $folder->authors()->insert($rows);
    }

    /**
     * @param  array{visibility: string, password?: string|null}  $validated
     */
    private function hashPassword(array $validated): ?string
    {
        if ($validated['visibility'] !== FolderVisibility::Password->value) {
            return null;
        }

        return Hash::make($validated['password']);
    }

    /**
     * @param  array{name: string, visibility: string}  $validated
     */
    private function resolvePublicSlug(Folder $folder, array $validated): ?string
    {
        $wasPrivate = $folder->visibility === FolderVisibility::Private;
        $isBecomingPublicFacing = $validated['visibility'] !== FolderVisibility::Private->value;

        if ($wasPrivate && $isBecomingPublicFacing) {
            return Folder::generatePublicSlugFor($validated['name'], $folder->id);
        }

        return null;
    }
}
