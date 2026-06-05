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
                'variants',
                'signs' => function ($query): void {
                    $query->orderBy('id')
                        ->select([
                            'id',
                            'name',
                            'public_url',
                            'mime_type',
                            'width',
                            'height',
                            'column_ratio',
                            'folder_id',
                            'variant_id',
                        ]);
                },
            ])
            ->withCount(['signs', 'variants']);

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

        $folder->load('variants');

        return response()->json([
            'folder' => new PublicFolderResource($folder),
            'signs' => PublicSignResource::collection($this->publicSignsForFolder($folder)->values()),
        ]);
    }

    public function unlock(Request $request, string $slug): JsonResponse
    {
        $folder = $this->resolveFolder($slug);

        if ($folder === null || $folder->visibility === FolderVisibility::Private) {
            abort(404);
        }

        if ($folder->visibility === FolderVisibility::Public) {
            $folder->load('variants');

            return response()->json([
                'folder' => new PublicFolderResource($folder),
                'signs' => PublicSignResource::collection($this->publicSignsForFolder($folder)->values()),
            ]);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! Hash::check($validated['password'], (string) $folder->password_hash)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        return response()->json([
            'folder' => new PublicFolderResource($folder),
            'signs' => PublicSignResource::collection($this->publicSignsForFolder($folder)->values()),
        ]);
    }

    public function signs(Request $request, string $slug): JsonResponse
    {
        $folder = $this->resolveFolder($slug);

        if ($folder === null || $folder->visibility === FolderVisibility::Private) {
            abort(404);
        }

        if ($folder->visibility === FolderVisibility::Password) {
            $password = (string) $request->input('password', '');
            if (! Hash::check($password, (string) $folder->password_hash)) {
                abort(403);
            }
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
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

        return PublicSignResource::collection($query->paginate($perPage))->response();
    }

    private function resolveFolder(string $slug): ?Folder
    {
        return Folder::query()
            ->where('public_slug', $slug)
            ->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Sign>
     */
    private function publicSignsForFolder(Folder $folder)
    {
        $defaultVariantId = $folder->defaultVariant?->id;

        $query = $folder->signs()
            ->latest()
            ->select(['id', 'name', 'public_url', 'mime_type', 'width', 'height', 'folder_id', 'variant_id', 'column_ratio']);

        if ($defaultVariantId !== null) {
            $query->where('variant_id', $defaultVariantId);
        }

        return $query->get();
    }
}
