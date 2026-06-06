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
        $query = User::query()->withCount(['folders', 'signs']);

        if ($search = $request->string('q', '')) {
            $query->where('display_name', 'like', '%'.$search.'%');
        }

        $users = $query->with('oauthProviders')->latest()->paginate(20);

        $users->through(function (User $user) {
            return [
                'id'           => $user->id,
                'display_name' => $user->display_name,
                'avatar_url'   => $user->avatar_url,
                'is_admin'     => $user->is_admin,
                'banned_at'    => $user->banned_at?->toISOString(),
                'ban_reason'   => $user->ban_reason,
                'folders_count' => $user->folders_count,
                'signs_count'  => $user->signs_count,
                'providers'    => $user->oauthProviders->map(fn ($p) => [
                    'provider' => $p->provider,
                    'username' => $p->username,
                ]),
            ];
        });

        return response()->json([
            ...$users->toArray(),
            'stats' => [
                'total' => User::count(),
                'admins' => User::where('is_admin', true)->count(),
                'banned' => User::whereNotNull('banned_at')->count(),
            ],
        ]);
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

    public function unban(User $user): JsonResponse
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
