<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BanUserAction;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function __construct(
        private BanUserAction $banUser,
        private ActivityLogService $activityLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->withCount(['folders', 'signs']);

        if ($search = $request->string('q', '')) {
            $query->where('display_name', 'like', '%'.$search.'%');
        }

        $users = $query->with('oauthProviders')->latest()->paginate(20);

        $users->through(function (User $user) {
            return [
                'id'            => $user->id,
                'display_name'  => $user->display_name,
                'avatar_url'    => $user->avatar_url,
                'is_admin'      => $user->is_admin,
                'banned_at'     => $user->banned_at?->toISOString(),
                'ban_reason'    => $user->ban_reason,
                'folders_count' => $user->folders_count,
                'signs_count'   => $user->signs_count,
                'providers'     => $user->oauthProviders->map(fn ($p) => [
                    'provider' => $p->provider,
                    'username' => $p->username,
                ]),
            ];
        });

        return response()->json([
            ...$users->toArray(),
            'stats' => [
                'total'  => User::count(),
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

        $targetName = $user->display_name;

        $this->banUser->handle($user, $validated['reason']);

        $this->activityLog->log(ActivityLog::ADMIN_USER_BANNED, $request->user()->id, [
            'subject_user_id' => $user->id,
            'metadata'        => ['reason' => $validated['reason'], 'target_name' => $targetName],
            'ip'              => $request->ip(),
        ]);

        return response()->json(['message' => 'User has been banned and their content removed.']);
    }

    public function unban(Request $request, User $user): JsonResponse
    {
        if (! $user->isBanned()) {
            throw ValidationException::withMessages([
                'user' => ['This user is not banned.'],
            ]);
        }

        $user->banned_at  = null;
        $user->ban_reason = null;
        $user->save();

        $this->activityLog->log(ActivityLog::ADMIN_USER_UNBANNED, $request->user()->id, [
            'subject_user_id' => $user->id,
            'metadata'        => ['target_name' => $user->display_name],
            'ip'              => $request->ip(),
        ]);

        return response()->json(['message' => 'User has been unbanned.']);
    }
}
