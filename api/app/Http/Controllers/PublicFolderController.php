<?php

namespace App\Http\Controllers;

use App\Enums\FolderViewType;
use App\Enums\FolderVisibility;
use App\Http\Resources\BrowseFolderResource;
use App\Http\Resources\PublicFolderResource;
use App\Http\Resources\PublicSignResource;
use App\Models\Folder;
use App\Models\FolderVote;
use App\Models\Sign;
use App\Services\EngagementTrackingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PublicFolderController extends Controller
{
    public function __construct(private EngagementTrackingService $engagementTracking) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Folder::query()
            ->where('visibility', FolderVisibility::Public)
            ->has('signs')
            ->with([
                'user:id,display_name,avatar_url',
                'defaultVariant:id,folder_id,grid_background_preset',
            ])
            ->withCount(['signs', 'variants', 'votes']);

        if ($user = $request->user('sanctum')) {
            $query->withExists([
                'votes as user_has_voted' => fn (Builder $query): Builder => $query->where('user_id', $user->id),
            ]);
        }

        if ($search = $request->string('q', '')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($request->input('sort') === 'latest') {
            $query->latest();
        } else {
            $query->orderByDesc('votes_count');
        }

        $folders = $query->paginate(10);
        $this->loadPreviewSigns($folders->getCollection());

        return BrowseFolderResource::collection($folders);
    }

    public function vote(Request $request, string $slug): JsonResponse
    {
        $folder = $this->resolveFolder($slug);

        if ($folder === null || $folder->visibility === FolderVisibility::Private) {
            abort(404);
        }

        $userId = $request->user()->id;
        $existing = FolderVote::where('folder_id', $folder->id)->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
            $userHasVoted = false;
        } else {
            FolderVote::create(['folder_id' => $folder->id, 'user_id' => $userId]);
            $userHasVoted = true;
        }

        return response()->json([
            'votes_count' => FolderVote::query()->where('folder_id', $folder->id)->count(),
            'user_has_voted' => $userHasVoted,
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $folder = $this->resolveFolderForContents($slug, $request);

        if ($folder === null || $folder->visibility === FolderVisibility::Private) {
            abort(404);
        }

        $this->engagementTracking->recordFolderView($folder, FolderViewType::Full, $request->ip());

        if ($folder->visibility === FolderVisibility::Password) {
            return response()->json([
                'requires_password' => true,
            ]);
        }

        return response()->json([
            'folder' => new PublicFolderResource($folder),
            'signs' => PublicSignResource::collection($this->publicSignsForFolder($folder)->values()),
        ]);
    }

    public function unlock(Request $request, string $slug): JsonResponse
    {
        $folder = $this->resolveFolderForContents($slug, $request);

        if ($folder === null || $folder->visibility === FolderVisibility::Private) {
            abort(404);
        }

        if ($folder->visibility === FolderVisibility::Public) {
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
        $folder = $this->resolveFolderForSigns($slug);

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

        $query = $folder->signs()
            ->orderBy('sort_key')
            ->select(['id', 'name', 'public_url', 'thumbnail_url', 'mime_type', 'width', 'height', 'column_ratio', 'variant_id']);

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

    public function trackPreviewView(Request $request, string $slug): Response
    {
        $folder = $this->resolveFolder($slug);

        if ($folder === null || $folder->visibility === FolderVisibility::Private) {
            abort(404);
        }

        $this->engagementTracking->recordFolderView($folder, FolderViewType::Preview, $request->ip());

        return response()->noContent();
    }

    public function trackSignCopy(Request $request, string $slug, Sign $sign): Response
    {
        $folder = $this->resolveFolder($slug);

        if ($folder === null || $folder->visibility === FolderVisibility::Private || $sign->folder_id !== $folder->id) {
            abort(404);
        }

        $this->engagementTracking->recordSignCopy($sign, $request->ip());

        return response()->noContent();
    }

    private function resolveFolder(string $slug): ?Folder
    {
        return Folder::query()
            ->where('public_slug', $slug)
            ->first();
    }

    private function resolveFolderForContents(string $slug, Request $request): ?Folder
    {
        $query = Folder::query()
            ->where('public_slug', $slug)
            ->with([
                'user:id,display_name,avatar_url',
                'defaultVariant:id,folder_id,grid_background_preset',
                'variants:id,folder_id,name,is_default,grid_background_preset',
            ])
            ->withCount('votes');

        if ($user = $request->user('sanctum')) {
            $query->withExists([
                'votes as user_has_voted' => fn (Builder $query): Builder => $query->where('user_id', $user->id),
            ]);
        }

        return $query->first();
    }

    private function resolveFolderForSigns(string $slug): ?Folder
    {
        return Folder::query()
            ->where('public_slug', $slug)
            ->with('defaultVariant:id,folder_id')
            ->first();
    }

    private function loadPreviewSigns(Collection $folders): void
    {
        if ($folders->isEmpty()) {
            return;
        }

        $foldersByVariant = $folders
            ->filter(fn (Folder $folder): bool => $folder->defaultVariant !== null)
            ->map(fn (Folder $folder): array => [
                'folder_id' => $folder->id,
                'variant_id' => $folder->defaultVariant->id,
            ])
            ->values();

        if ($foldersByVariant->isEmpty()) {
            foreach ($folders as $folder) {
                $folder->setRelation('previewSigns', collect());
            }

            return;
        }

        $aspectBucket = <<<'SQL'
CASE
    WHEN width IS NULL OR height IS NULL OR height = 0 THEN 'unknown'
    WHEN width / height < 1.5 THEN '1:1'
    WHEN width / height < 3 THEN '2:1'
    WHEN width / height < 5 THEN '4:1'
    ELSE 'wide'
END
SQL;

        $rankedSigns = DB::table('signs')
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
            ])
            ->selectRaw("{$aspectBucket} as aspect_bucket")
            ->selectRaw("ROW_NUMBER() OVER (PARTITION BY folder_id, {$aspectBucket} ORDER BY id) as bucket_rank")
            ->where(function ($query) use ($foldersByVariant): void {
                foreach ($foldersByVariant as $folder) {
                    $query->orWhere(function ($query) use ($folder): void {
                        $query
                            ->where('folder_id', $folder['folder_id'])
                            ->where('variant_id', $folder['variant_id']);
                    });
                }
            });

        $previewSigns = DB::query()
            ->fromSub($rankedSigns, 'ranked_signs')
            ->where('bucket_rank', '<=', 6)
            ->orderBy('folder_id')
            ->orderByRaw("
                CASE aspect_bucket
                    WHEN '1:1' THEN 1
                    WHEN '2:1' THEN 2
                    WHEN '4:1' THEN 3
                    WHEN 'wide' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('id')
            ->get()
            ->groupBy('folder_id');

        foreach ($folders as $folder) {
            $folder->setRelation('previewSigns', $previewSigns->get($folder->id, collect()));
        }
    }

    /**
     * @return Collection<int, Sign>
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
