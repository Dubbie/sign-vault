<?php

namespace App\Services;

use App\Enums\FolderViewType;
use App\Models\Folder;
use App\Models\FolderView;
use App\Models\Sign;
use App\Models\SignCopy;
use App\Models\VisitorSession;

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
