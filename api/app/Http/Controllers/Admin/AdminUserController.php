<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->withCount(['folders', 'signs'])
            ->latest()
            ->paginate(20);

        $users->through(function (User $user) {
            return [
                'id' => $user->id,
                'discord_id' => $user->discord_id,
                'discord_username' => $user->discord_username,
                'discord_global_name' => $user->discord_global_name,
                'discord_avatar' => $user->discord_avatar,
                'is_admin' => $user->is_admin,
                'banned_at' => $user->banned_at?->toISOString(),
                'ban_reason' => $user->ban_reason,
                'folders_count' => $user->folders_count,
                'signs_count' => $user->signs_count,
            ];
        });

        return response()->json($users);
    }

    public function ban(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages([
                'user' => ['You cannot ban yourself.'],
            ]);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->tokens()->delete();

        foreach ($user->folders as $folder) {
            foreach ($folder->signs as $sign) {
                Storage::disk($sign->storage_disk)->delete($sign->storage_key);
                $sign->delete();
            }

            $folder->delete();
        }

        $user->banned_at = now();
        $user->ban_reason = $validated['reason'];
        $user->save();

        return response()->json(['message' => 'User has been banned and their content removed.']);
    }

    public function unban(Request $request, User $user): JsonResponse
    {
        if (! $user->isBanned()) {
            throw ValidationException::withMessages([
                'user' => ['This user is not banned.'],
            ]);
        }

        $user->banned_at = null;
        $user->ban_reason = null;
        $user->save();

        return response()->json(['message' => 'User has been unbanned.']);
    }
}
