<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class ActivityLogService
{
    /**
     * @param array{
     *   subject_user_id?: int|null,
     *   subject_folder_id?: int|null,
     *   subject_sign_id?: int|null,
     *   metadata?: array<string, mixed>|null,
     *   ip?: string|null,
     *   upload_session_id?: string|null,
     * } $options
     */
    public function log(string $event, ?int $actorId, array $options = []): void
    {
        ActivityLog::create([
            'event' => $event,
            'actor_id' => $actorId,
            'subject_user_id' => $options['subject_user_id'] ?? null,
            'subject_folder_id' => $options['subject_folder_id'] ?? null,
            'subject_sign_id' => $options['subject_sign_id'] ?? null,
            'metadata' => $options['metadata'] ?? null,
            'ip_address' => $options['ip'] ?? null,
            'upload_session_id' => $options['upload_session_id'] ?? null,
        ]);
    }

    public function logUploadedSigns(
        int $actorId,
        int $folderId,
        string $folderName,
        int $count,
        ?string $ip = null,
        ?string $uploadSessionId = null,
    ): void {
        if ($uploadSessionId === null) {
            $this->log(ActivityLog::SIGNS_UPLOADED, $actorId, [
                'subject_folder_id' => $folderId,
                'metadata' => [
                    'folder_id' => $folderId,
                    'folder_name' => $folderName,
                    'count' => $count,
                ],
                'ip' => $ip,
            ]);

            return;
        }

        DB::transaction(function () use ($actorId, $folderId, $folderName, $count, $ip, $uploadSessionId): void {
            $activityLog = ActivityLog::query()
                ->where('event', ActivityLog::SIGNS_UPLOADED)
                ->where('actor_id', $actorId)
                ->where('subject_folder_id', $folderId)
                ->where('upload_session_id', $uploadSessionId)
                ->lockForUpdate()
                ->first();

            if (! $activityLog) {
                $this->log(ActivityLog::SIGNS_UPLOADED, $actorId, [
                    'subject_folder_id' => $folderId,
                    'metadata' => [
                        'folder_id' => $folderId,
                        'folder_name' => $folderName,
                        'count' => $count,
                    ],
                    'ip' => $ip,
                    'upload_session_id' => $uploadSessionId,
                ]);

                return;
            }

            $metadata = $activityLog->metadata ?? [];
            $existingCount = (int) ($metadata['count'] ?? 0);

            $activityLog->update([
                'metadata' => [
                    'folder_id' => $folderId,
                    'folder_name' => $folderName,
                    'count' => $existingCount + $count,
                ],
                'ip_address' => $ip,
            ]);
        });
    }
}
