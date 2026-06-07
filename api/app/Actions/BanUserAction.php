<?php

namespace App\Actions;

use App\Models\User;
use App\Services\SignDeletionService;

class BanUserAction
{
    public function __construct(private SignDeletionService $signDeletion) {}

    public function handle(User $user, string $reason): void
    {
        $user->tokens()->delete();

        foreach ($user->folders as $folder) {
            $this->signDeletion->deleteFolder($folder);
        }

        $user->banned_at = now();
        $user->ban_reason = $reason;
        $user->save();
    }
}
