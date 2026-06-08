<?php

namespace App\Services;

use App\Enums\FolderViewType;
use App\Models\Folder;
use App\Models\FolderView;
use App\Models\Sign;
use App\Models\SignCopy;
use Illuminate\Support\Facades\DB;

class EngagementTrackingService
{
    public function recordFolderView(Folder $folder, FolderViewType $viewType, ?string $ip): void
    {
        $ipHash = $this->hashIp($ip);

        if ($ipHash === null) {
            return;
        }

        DB::transaction(function () use ($folder, $viewType, $ipHash): void {
            $view = FolderView::query()
                ->where('folder_id', $folder->id)
                ->where('ip_hash', $ipHash)
                ->where('view_type', $viewType)
                ->lockForUpdate()
                ->first();

            if ($view) {
                $view->update(['last_seen_at' => now()]);

                return;
            }

            FolderView::create([
                'folder_id' => $folder->id,
                'ip_hash' => $ipHash,
                'view_type' => $viewType,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);
        });
    }

    public function recordSignCopy(Sign $sign, ?string $ip): void
    {
        $ipHash = $this->hashIp($ip);

        if ($ipHash === null) {
            return;
        }

        DB::transaction(function () use ($sign, $ipHash): void {
            $copy = SignCopy::query()
                ->where('sign_id', $sign->id)
                ->where('ip_hash', $ipHash)
                ->lockForUpdate()
                ->first();

            if ($copy) {
                $copy->update(['last_seen_at' => now()]);

                return;
            }

            SignCopy::create([
                'sign_id' => $sign->id,
                'folder_id' => $sign->folder_id,
                'ip_hash' => $ipHash,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);
        });
    }

    private function hashIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
