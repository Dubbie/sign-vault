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
use App\Services\FolderPreviewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PublicFolderController extends Controller
{
    public function __construct(
        private EngagementTrackingService $engagementTracking,
        private FolderPreviewService $folderPreview,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->publicFolderIndexQuery($request);

        if ($search = $request->string('q', '')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($request->input('sort') === 'latest') {
            $query->latest();
        } else {
            $query->orderByDesc('votes_count');
        }

        $folders = $query->paginate(10);
        $this->folderPreview->loadPreviewSigns($folders->getCollection());

        return BrowseFolderResource::collection($folders);
    }

    public function vote(Request $request, string $slug): JsonResponse
    {
        $folder = $this->ensureFolderIsVisible($this->resolveFolder($slug));

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
            'votes_count' => $folder->votes()->count(),
            'user_has_voted' => $userHasVoted,
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $folder = $this->ensureFolderIsVisible($this->resolveFolderForContents($slug, $request));

        $this->engagementTracking->recordFolderView($folder, FolderViewType::Full, $request->ip());

        if ($folder->visibility === FolderVisibility::Password) {
            return response()->json([
                'requires_password' => true,
            ]);
        }

        return $this->folderContentsResponse($folder);
    }

    public function unlock(Request $request, string $slug): JsonResponse
    {
        $folder = $this->ensureFolderIsVisible($this->resolveFolderForContents($slug, $request));

        if ($folder->visibility === FolderVisibility::Public) {
            return $this->folderContentsResponse($folder);
        }

        $this->validateFolderPassword($request, $folder);

        return $this->folderContentsResponse($folder);
    }

    public function signs(Request $request, string $slug): JsonResponse
    {
        $folder = $this->ensureFolderIsVisible($this->resolveFolderForSigns($slug));
        $this->ensureSignsAccessAllowed($request, $folder);

        $perPage = min((int) $request->input('per_page', 10), 100);
        $query = $this->publicSignsQuery($folder, $request);

        return PublicSignResource::collection($query->paginate($perPage))->response();
    }

    public function trackSignCopy(Request $request, string $slug, Sign $sign): Response
    {
        $folder = $this->ensureFolderIsVisible($this->resolveFolder($slug));

        if ($sign->folder_id !== $folder->id) {
            abort(404);
        }

        $this->ensureSignsAccessAllowed($request, $folder);

        $this->engagementTracking->recordSignCopy($sign, $request->ip());

        return response()->noContent();
    }

    private function resolveFolder(string $slug): ?Folder
    {
        return Folder::query()
            ->where('public_slug', $slug)
            ->first();
    }

    private function publicFolderIndexQuery(Request $request): Builder
    {
        return $this->withUserVoteState(
            Folder::query()
                ->where('visibility', FolderVisibility::Public)
                ->has('signs')
                ->with([
                    'authors',
                    'user:id,display_name,avatar_url',
                    'defaultVariant:id,folder_id,grid_background_preset',
                ])
                ->withCount(['signs', 'variants', 'votes']),
            $request
        );
    }

    private function resolveFolderForContents(string $slug, Request $request): ?Folder
    {
        $query = $this->withUserVoteState(
            Folder::query()
                ->where('public_slug', $slug)
                ->with([
                    'authors',
                    'user:id,display_name,avatar_url',
                    'defaultVariant:id,folder_id,grid_background_preset',
                    'variants:id,folder_id,name,is_default,sort_order,grid_background_preset',
                ])
                ->withCount('votes'),
            $request
        );

        return $query->first();
    }

    private function resolveFolderForSigns(string $slug): ?Folder
    {
        return Folder::query()
            ->where('public_slug', $slug)
            ->with('defaultVariant:id,folder_id')
            ->first();
    }

    private function withUserVoteState(Builder $query, Request $request): Builder
    {
        if ($user = $request->user('sanctum')) {
            $query->withExists([
                'votes as user_has_voted' => fn (Builder $voteQuery): Builder => $voteQuery->where('user_id', $user->id),
            ]);
        }

        return $query;
    }

    private function ensureFolderIsVisible(?Folder $folder): Folder
    {
        if ($folder === null || $folder->visibility === FolderVisibility::Private) {
            abort(404);
        }

        return $folder;
    }

    private function validateFolderPassword(Request $request, Folder $folder): void
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! Hash::check($validated['password'], (string) $folder->password_hash)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }
    }

    private function ensureSignsAccessAllowed(Request $request, Folder $folder): void
    {
        if ($folder->visibility !== FolderVisibility::Password) {
            return;
        }

        $password = (string) $request->input('password', '');

        if (! Hash::check($password, (string) $folder->password_hash)) {
            abort(403);
        }
    }

    private function publicSignsQuery(Folder $folder, Request $request): HasMany
    {
        $defaultVariantId = $folder->defaultVariant?->id;

        $query = $folder->signs()
            ->orderBy('sort_key')
            ->select(['id', 'name', 'public_url', 'thumbnail_url', 'mime_type', 'width', 'height', 'column_ratio', 'variant_id', 'folder_id']);

        if ($variantId = $request->integer('variant_id')) {
            $query->where('variant_id', $variantId);
        } elseif ($defaultVariantId !== null) {
            $query->where('variant_id', $defaultVariantId);
        }

        if ($columnRatio = $request->integer('column_ratio')) {
            $query->where('column_ratio', $columnRatio);
        }

        return $query;
    }

    /**
     * @return Collection<int, Sign>
     */
    private function publicSignsForFolder(Folder $folder)
    {
        $defaultVariantId = $folder->defaultVariant?->id;

        $query = $folder->signs()
            ->orderBy('sort_key')
            ->select(['id', 'name', 'public_url', 'thumbnail_url', 'mime_type', 'width', 'height', 'folder_id', 'variant_id', 'column_ratio']);

        if ($defaultVariantId !== null) {
            $query->where('variant_id', $defaultVariantId);
        }

        return $query->get();
    }

    private function folderContentsResponse(Folder $folder): JsonResponse
    {
        return response()->json([
            'folder' => new PublicFolderResource($folder),
            'signs' => PublicSignResource::collection($this->publicSignsForFolder($folder)->values()),
        ]);
    }
}
