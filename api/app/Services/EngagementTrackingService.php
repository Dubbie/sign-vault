<?php

namespace App\Services;

use App\Enums\FolderViewType;
use App\Models\Folder;
use App\Models\FolderView;
use App\Models\Sign;
use App\Models\SignCopy;
use App\Models\VisitorSession;
use Illuminate\Support\Facades\DB;

class EngagementTrackingService
{
    public function recordFolderView(Folder $folder, FolderViewType $viewType, ?string $ip): void
    {
        $ipHash = $this->hashIp($ip);

        if ($ipHash === null) {
            return;
        }

        $this->recordSession($ipHash);

        $timestamp = now();

        FolderView::query()->upsert([
            [
                'folder_id' => $folder->id,
                'ip_hash' => $ipHash,
                'view_type' => $viewType,
                'first_seen_at' => $timestamp,
                'last_seen_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['folder_id', 'ip_hash', 'view_type'], ['last_seen_at', 'updated_at']);

        $this->bumpDailyCount('folder_view_daily_counts', ['folder_id' => $folder->id]);
    }

    public function recordSignCopy(Sign $sign, ?string $ip): void
    {
        $ipHash = $this->hashIp($ip);

        if ($ipHash === null) {
            return;
        }

        $this->recordSession($ipHash);

        $timestamp = now();

        SignCopy::query()->upsert([
            [
                'sign_id' => $sign->id,
                'folder_id' => $sign->folder_id,
                'ip_hash' => $ipHash,
                'first_seen_at' => $timestamp,
                'last_seen_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['sign_id', 'ip_hash'], ['last_seen_at', 'updated_at']);

        $this->bumpDailyCount('sign_copy_daily_counts', ['sign_id' => $sign->id, 'folder_id' => $sign->folder_id]);
    }

    /**
     * Increment a per-day counter row, creating it if it doesn't exist yet.
     *
     * @param  array<string, int>  $keyColumns
     */
    private function bumpDailyCount(string $table, array $keyColumns): void
    {
        $now = now();
        $where = [...$keyColumns, 'date' => today()->toDateString()];

        $updated = DB::table($table)->where($where)->increment('count', 1, ['updated_at' => $now]);

        if ($updated > 0) {
            return;
        }

        DB::table($table)->insertOrIgnore([
            ...$where,
            'count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table($table)->where($where)->where('count', '<', 1)->increment('count', 1, ['updated_at' => $now]);
    }

    private function recordSession(string $ipHash): void
    {
        $now = now();

        VisitorSession::query()->upsert(
            [[
                'ip_hash' => $ipHash,
                'session_date' => today()->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['ip_hash', 'session_date'],
            ['updated_at']
        );
    }

    private function hashIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
