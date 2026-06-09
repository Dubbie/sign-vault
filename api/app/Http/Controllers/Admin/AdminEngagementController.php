<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FolderViewType;
use App\Http\Controllers\Controller;
use App\Models\FolderView;
use App\Models\SignCopy;
use App\Models\VisitorSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminEngagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $days = min(max((int) $request->query('days', 30), 1), 365);
        $since = Carbon::now()->subDays($days)->startOfDay();

        $folderFullViews = FolderView::query()
            ->where('view_type', FolderViewType::Full)
            ->where('first_seen_at', '>=', $since)
            ->distinct('ip_hash')
            ->count('ip_hash');

        $returningVisitors = VisitorSession::query()
            ->where('session_date', '>=', $since->toDateString())
            ->groupBy('ip_hash')
            ->havingRaw('COUNT(DISTINCT session_date) >= 2')
            ->distinct()
            ->count('ip_hash');

        $signCopies = SignCopy::query()
            ->where('first_seen_at', '>=', $since)
            ->distinct('ip_hash')
            ->count('ip_hash');

        return response()->json([
            'summary' => [
                'folder_full_views' => $folderFullViews,
                'returning_visitors' => $returningVisitors,
                'sign_copies' => $signCopies,
            ],
            'top_folders' => $this->topFolders($since),
            'top_signs' => $this->topSigns($since),
            'timeseries' => [
                'folder_full_views' => $this->folderViewsTimeseries($since),
                'sign_copies' => $this->signCopiesTimeseries($since),
            ],
        ]);
    }

    private function topFolders(Carbon $since): array
    {
        return FolderView::query()
            ->select('folder_id')
            ->selectRaw('COUNT(*) as full_views')
            ->where('view_type', FolderViewType::Full)
            ->where('first_seen_at', '>=', $since)
            ->groupBy('folder_id')
            ->orderByDesc('full_views')
            ->limit(10)
            ->with('folder:id,name')
            ->get()
            ->map(fn (FolderView $row) => [
                'folder_id' => $row->folder_id,
                'folder_name' => $row->folder?->name,
                'full_views' => (int) $row->full_views,
            ])
            ->all();
    }

    private function topSigns(Carbon $since): array
    {
        return SignCopy::query()
            ->select('sign_id', 'folder_id')
            ->selectRaw('COUNT(*) as copies')
            ->where('first_seen_at', '>=', $since)
            ->groupBy('sign_id', 'folder_id')
            ->orderByDesc('copies')
            ->limit(10)
            ->with(['sign:id,name', 'folder:id,name'])
            ->get()
            ->map(fn (SignCopy $row) => [
                'sign_id' => $row->sign_id,
                'sign_name' => $row->sign?->name,
                'folder_id' => $row->folder_id,
                'folder_name' => $row->folder?->name,
                'copies' => (int) $row->copies,
            ])
            ->all();
    }

    private function folderViewsTimeseries(Carbon $since): array
    {
        return FolderView::query()
            ->selectRaw('DATE(first_seen_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->where('view_type', FolderViewType::Full)
            ->where('first_seen_at', '>=', $since)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count])
            ->all();
    }

    private function signCopiesTimeseries(Carbon $since): array
    {
        return SignCopy::query()
            ->selectRaw('DATE(first_seen_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->where('first_seen_at', '>=', $since)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count])
            ->all();
    }
}
