<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogService
{
    /**
     * @param array{
     *   subject_user_id?: int|null,
     *   subject_folder_id?: int|null,
     *   subject_sign_id?: int|null,
     *   metadata?: array<string, mixed>|null,
     *   ip?: string|null,
     * } $options
     */
    public function log(string $event, ?int $actorId, array $options = []): void
    {
        ActivityLog::create([
            'event'             => $event,
            'actor_id'          => $actorId,
            'subject_user_id'   => $options['subject_user_id'] ?? null,
            'subject_folder_id' => $options['subject_folder_id'] ?? null,
            'subject_sign_id'   => $options['subject_sign_id'] ?? null,
            'metadata'          => $options['metadata'] ?? null,
            'ip_address'        => $options['ip'] ?? null,
        ]);
    }
}
