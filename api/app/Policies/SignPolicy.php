<?php

namespace App\Policies;

use App\Models\Sign;
use App\Models\User;

class SignPolicy
{
    public function view(User $user, Sign $sign): bool
    {
        return $sign->user_id === $user->id;
    }

    public function delete(User $user, Sign $sign): bool
    {
        return $sign->user_id === $user->id;
    }
}
