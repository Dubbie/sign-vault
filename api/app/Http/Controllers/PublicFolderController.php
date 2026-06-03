<?php

namespace App\Http\Controllers;

use App\Enums\FolderVisibility;
use App\Http\Resources\BrowseFolderResource;
use App\Http\Resources\PublicFolderResource;
use App\Http\Resources\PublicSignResource;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PublicFolderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Folder::query()
            ->where('visibility', FolderVisibility::Public)
            ->has('signs')
            ->with([
                'user',
                'signs' => function ($query): void {
                    $query->orderBy('id')
                        ->select(['id', 'name', 'public_url', 'width', 'height', 'folder_id']);
                },
            ])
            ->withCount('signs');

        if ($search = $request->string('q', '')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $folders = $query->latest()->paginate(20);

        return BrowseFolderResource::collection($folders);
    }

    public function show(string $slug): JsonResponse
    {
        $folder = $this->resolveFolder($slug);

        if ($folder === null || $folder->visibility === FolderVisibility::Private) {
            abort(404);
        }

        if ($folder->visibility === FolderVisibility::Password) {
            return response()->json([
                'requires_password' => true,
            ]);
        }

        return $this->folderResponse($folder);
    }

    public function unlock(Request $request, string $slug): JsonResponse
    {
        $folder = $this->resolveFolder($slug);

        if ($folder === null || $folder->visibility === FolderVisibility::Private) {
            abort(404);
        }

        if ($folder->visibility === FolderVisibility::Public) {
            return $this->folderResponse($folder);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! Hash::check($validated['password'], (string) $folder->password_hash)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        return $this->folderResponse($folder);
    }

    private function resolveFolder(string $slug): ?Folder
    {
        return Folder::query()
            ->where('public_slug', $slug)
            ->first();
    }

    private function folderResponse(Folder $folder): JsonResponse
    {
        $signs = $folder->signs()
            ->latest()
            ->get();

        return response()->json([
            'folder' => new PublicFolderResource($folder),
            'signs' => PublicSignResource::collection($signs),
        ]);
    }
}
