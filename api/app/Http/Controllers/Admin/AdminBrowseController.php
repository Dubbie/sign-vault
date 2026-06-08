<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrowseFolderResource;
use App\Http\Resources\PublicSignResource;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminBrowseController extends Controller
{
    public function folders(Request $request): AnonymousResourceCollection
    {
        $query = Folder::query()
            ->with([
                'user',
                'signs' => function ($query): void {
                    $query->orderBy('id')
                        ->select([
                            'id',
                            'name',
                            'public_url',
                            'thumbnail_url',
                            'mime_type',
                            'width',
                            'height',
                            'column_ratio',
                            'folder_id',
                            'variant_id',
                        ]);
                },
            ])
            ->withCount('signs');

        if ($search = $request->string('q', '')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $folders = $query->latest()->paginate(20);

        return BrowseFolderResource::collection($folders);
    }

    public function folderSigns(Folder $folder): JsonResponse
    {
        $signs = $folder->signs()
            ->latest()
            ->get();

        return response()->json([
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'slug' => $folder->public_slug,
                'user_id' => $folder->user_id,
            ],
            'signs' => PublicSignResource::collection($signs),
        ]);
    }
}
