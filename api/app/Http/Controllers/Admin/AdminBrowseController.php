<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrowseFolderResource;
use App\Http\Resources\PublicSignResource;
use App\Models\Folder;
use App\Services\FolderPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminBrowseController extends Controller
{
    public function __construct(private FolderPreviewService $folderPreview) {}

    public function folders(Request $request): AnonymousResourceCollection
    {
        $query = Folder::query()
            ->with([
                'user:id,display_name,avatar_url',
                'defaultVariant:id,folder_id,grid_background_preset',
            ])
            ->withCount(['signs', 'variants']);

        if ($search = $request->string('q', '')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $folders = $query->latest()->paginate(20);
        $this->folderPreview->loadPreviewSigns($folders->getCollection());

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
