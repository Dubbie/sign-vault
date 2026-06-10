<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FolderViewDailyCount;
use App\Models\SignCopyDailyCount;
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
        $sinceDate = $since->toDateString();

        return response()->json([
            'summary' => [
                'total_visitors' => $this->totalVisitors($sinceDate),
                'new_visitors' => $this->newVisitors($sinceDate),
                'returning_visitors' => $this->returningVisitors($sinceDate),
                'folder_opens' => $this->totalFolderOpens($sinceDate),
                'sign_copies' => $this->totalSignCopies($sinceDate),
            ],
            'top_folders' => $this->topFolders($sinceDate),
            'top_signs' => $this->topSigns($sinceDate),
            'timeseries' => [
                'daily_active_visitors' => $this->dailyActiveVisitorsTimeseries($sinceDate),
                'new_vs_returning' => $this->newVsReturningTimeseries($sinceDate),
                'folder_opens' => $this->folderOpensTimeseries($sinceDate),
                'sign_copies' => $this->signCopiesTimeseries($sinceDate),
            ],
        ]);
    }

    private function totalVisitors(string $sinceDate): int
    {
        return VisitorSession::query()
            ->where('session_date', '>=', $sinceDate)
            ->distinct('ip_hash')
            ->count('ip_hash');
    }

    private function newVisitors(string $sinceDate): int
    {
        return VisitorSession::query()
            ->select('ip_hash')
            ->groupBy('ip_hash')
            ->havingRaw('MIN(session_date) >= ?', [$sinceDate])
            ->get()
            ->count();
    }

    private function returningVisitors(string $sinceDate): int
    {
        return VisitorSession::query()
            ->where('session_date', '>=', $sinceDate)
            ->groupBy('ip_hash')
            ->havingRaw('COUNT(DISTINCT session_date) >= 2')
            ->distinct()
            ->count('ip_hash');
    }

    private function totalFolderOpens(string $sinceDate): int
    {
        return (int) FolderViewDailyCount::query()
            ->where('date', '>=', $sinceDate)
            ->sum('count');
    }

    private function totalSignCopies(string $sinceDate): int
    {
        return (int) SignCopyDailyCount::query()
            ->where('date', '>=', $sinceDate)
            ->sum('count');
    }

    private function topFolders(string $sinceDate): array
    {
        return FolderViewDailyCount::query()
            ->select('folder_id')
            ->selectRaw('SUM(count) as opens')
            ->where('date', '>=', $sinceDate)
            ->groupBy('folder_id')
            ->orderByDesc('opens')
            ->limit(10)
            ->with('folder:id,name')
            ->get()
            ->map(fn (FolderViewDailyCount $row) => [
                'folder_id' => $row->folder_id,
                'folder_name' => $row->folder?->name,
                'opens' => (int) $row->opens,
            ])
            ->all();
    }

    private function topSigns(string $sinceDate): array
    {
        return SignCopyDailyCount::query()
            ->select('sign_id', 'folder_id')
            ->selectRaw('SUM(count) as copies')
            ->where('date', '>=', $sinceDate)
            ->groupBy('sign_id', 'folder_id')
            ->orderByDesc('copies')
            ->limit(10)
            ->with(['sign:id,name', 'folder:id,name'])
            ->get()
            ->map(fn (SignCopyDailyCount $row) => [
                'sign_id' => $row->sign_id,
                'sign_name' => $row->sign?->name,
                'folder_id' => $row->folder_id,
                'folder_name' => $row->folder?->name,
                'copies' => (int) $row->copies,
            ])
            ->all();
    }

    private function dailyActiveVisitorsTimeseries(string $sinceDate): array
    {
        return VisitorSession::query()
            ->selectRaw('session_date as date')
            ->selectRaw('COUNT(*) as count')
            ->where('session_date', '>=', $sinceDate)
            ->groupBy('session_date')
            ->orderBy('session_date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count])
            ->all();
    }

    private function newVsReturningTimeseries(string $sinceDate): array
    {
        $firstSeen = VisitorSession::query()
            ->select('ip_hash')
            ->selectRaw('MIN(session_date) as first_date')
            ->groupBy('ip_hash');

        return VisitorSession::query()
            ->joinSub($firstSeen, 'first_seen', function ($join) {
                $join->on('visitor_sessions.ip_hash', '=', 'first_seen.ip_hash');
            })
            ->where('visitor_sessions.session_date', '>=', $sinceDate)
            ->selectRaw('visitor_sessions.session_date as date')
            ->selectRaw('SUM(CASE WHEN visitor_sessions.session_date = first_seen.first_date THEN 1 ELSE 0 END) as new_count')
            ->selectRaw('SUM(CASE WHEN visitor_sessions.session_date != first_seen.first_date THEN 1 ELSE 0 END) as returning_count')
            ->groupBy('visitor_sessions.session_date')
            ->orderBy('visitor_sessions.session_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'new' => (int) $row->new_count,
                'returning' => (int) $row->returning_count,
            ])
            ->all();
    }

    private function folderOpensTimeseries(string $sinceDate): array
    {
        return FolderViewDailyCount::query()
            ->selectRaw('date')
            ->selectRaw('SUM(count) as count')
            ->where('date', '>=', $sinceDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count])
            ->all();
    }

    private function signCopiesTimeseries(string $sinceDate): array
    {
        return SignCopyDailyCount::query()
            ->selectRaw('date')
            ->selectRaw('SUM(count) as count')
            ->where('date', '>=', $sinceDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count])
            ->all();
    }
}
