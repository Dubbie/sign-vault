<?php

namespace App\Http\Controllers;

use App\Enums\FolderVisibility;
use App\Http\Requests\Folder\StoreFolderRequest;
use App\Http\Requests\Folder\UpdateFolderRequest;
use App\Http\Resources\FolderResource;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FolderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $folders = $request->user()
            ->folders()
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
            'visibility' => $validated['visibility'],
            'password_hash' => $this->passwordHashFor($validated),
        ]);

        return (new FolderResource($folder->refresh()))->response()->setStatusCode(201);
    }

    public function show(Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        return (new FolderResource($folder))->response();
    }

    public function update(UpdateFolderRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();

        $folder->fill([
            'name' => $validated['name'],
            'slug' => Folder::generateSlugFor($request->user(), $validated['name'], $folder->id),
            'visibility' => $validated['visibility'],
        ]);

        $folder->password_hash = $this->passwordHashFor($validated);

        $folder->save();

        return (new FolderResource($folder->refresh()))->response();
    }

    public function destroy(Folder $folder): JsonResponse
    {
        $this->authorize('delete', $folder);

        // TODO: When signs exist, make deletion respect folder contents intentionally.
        $folder->delete();

        return response()->json([
            'message' => 'Folder deleted.',
        ]);
    }

    /**
     * @param  array{name:string,visibility:string,password?:string|null}  $validated
     */
    private function passwordHashFor(array $validated): ?string
    {
        if ($validated['visibility'] !== FolderVisibility::Password->value) {
            return null;
        }

        return Hash::make($validated['password']);
    }
}
