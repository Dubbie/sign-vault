<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OauthCallbackRequest;
use App\Models\ActivityLog;
use App\Models\OauthProvider;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\OauthIdentityService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Symfony\Component\HttpFoundation\Response;

class OauthAuthController extends Controller
{
    private const STATE_TTL_MINUTES = 10;

    private const VALID_PROVIDERS = [OauthProvider::DISCORD, OauthProvider::TRACKMANIA];

    public function __construct(
        private OauthIdentityService $oauthIdentity,
        private ActivityLogService $activityLog,
    ) {}

    public function redirect(string $provider, SocialiteFactory $socialite, CacheRepository $cache): JsonResponse
    {
        $this->assertValidProvider($provider);

        return $this->buildRedirectResponse($provider, null, $socialite, $cache);
    }

    public function callback(
        string $provider,
        OauthCallbackRequest $request,
        SocialiteFactory $socialite,
        CacheRepository $cache
    ): JsonResponse {
        $this->assertValidProvider($provider);

        $validated = $request->validated();
        $cached    = $cache->pull($this->stateCacheKey($provider, $validated['state']));

        if ($cached === null) {
            throw ValidationException::withMessages([
                'state' => ['The OAuth state is invalid or expired.'],
            ]);
        }

        $externalUser = $socialite->driver($provider)->stateless()->user();

        if (! empty($cached['link_user_id'])) {
            $currentUser = User::findOrFail($cached['link_user_id']);
            $user        = $this->oauthIdentity->link($currentUser, $provider, $externalUser);

            $this->activityLog->log(ActivityLog::PROVIDER_LINKED, $currentUser->id, [
                'metadata' => ['provider' => $provider],
                'ip'       => $request->ip(),
            ]);

            return response()->json([
                'message' => ucfirst($provider).' account linked successfully.',
                'user'    => $this->userResponse($user->loadCount(['folders', 'signs'])),
            ]);
        }

        $user = $this->oauthIdentity->login($provider, $externalUser);

        if ($user->isBanned()) {
            return response()->json([
                'message'    => 'Your account has been banned.',
                'ban_reason' => $user->ban_reason,
            ], Response::HTTP_FORBIDDEN);
        }

        $event = $user->wasRecentlyCreated ? ActivityLog::REGISTERED : ActivityLog::LOGIN;
        $this->activityLog->log($event, $user->id, [
            'metadata' => ['provider' => $provider],
            'ip'       => $request->ip(),
        ]);

        $token = $user->createToken($provider);

        return response()->json([
            'token' => $token->plainTextToken,
            'user'  => $this->userResponse($user->loadCount(['folders', 'signs'])),
        ]);
    }

    public function link(
        string $provider,
        Request $request,
        SocialiteFactory $socialite,
        CacheRepository $cache
    ): JsonResponse {
        $this->assertValidProvider($provider);

        return $this->buildRedirectResponse($provider, $request->user()->id, $socialite, $cache);
    }

    public function unlink(string $provider, Request $request): JsonResponse
    {
        $this->assertValidProvider($provider);

        $user = $request->user();

        $deleted = DB::transaction(function () use ($user, $provider): int {
            $count = $user->oauthProviders()->lockForUpdate()->count();

            if ($count <= 1) {
                throw ValidationException::withMessages([
                    'provider' => ['You cannot unlink your only login method.'],
                ]);
            }

            return $user->oauthProviders()
                ->where('provider', $provider)
                ->delete();
        });

        if (! $deleted) {
            throw ValidationException::withMessages([
                'provider' => ['This provider is not linked to your account.'],
            ]);
        }

        $this->activityLog->log(ActivityLog::PROVIDER_UNLINKED, $user->id, [
            'metadata' => ['provider' => $provider],
            'ip'       => $request->ip(),
        ]);

        return response()->json(['message' => ucfirst($provider).' has been unlinked.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isBanned()) {
            return response()->json([
                'banned'     => true,
                'ban_reason' => $user->ban_reason,
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'user'   => $this->userResponse($user->loadCount(['folders', 'signs'])),
            'limits' => [
                'sign_upload_max_files' => config('signs.max_upload_files'),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $actorId = $request->user()?->id;
        $request->user()?->currentAccessToken()?->delete();

        if ($actorId !== null) {
            $this->activityLog->log(ActivityLog::LOGOUT, $actorId, ['ip' => $request->ip()]);
        }

        return response()->json(['message' => 'Logged out.']);
    }

    /** @return array<string, bool|int|string|null> */
    private function userResponse(User $user): array
    {
        return $user->only([
            'id',
            'display_name',
            'avatar_url',
            'email',
            'is_admin',
            'folders_count',
            'signs_count',
        ]);
    }

    private function buildRedirectResponse(
        string $provider,
        ?int $linkUserId,
        SocialiteFactory $socialite,
        CacheRepository $cache
    ): JsonResponse {
        $state = Str::random(64);

        $cache->put(
            $this->stateCacheKey($provider, $state),
            ['link_user_id' => $linkUserId],
            now()->addMinutes(self::STATE_TTL_MINUTES)
        );

        $url = $socialite->driver($provider)
            ->stateless()
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url, 'state' => $state]);
    }

    private function stateCacheKey(string $provider, string $state): string
    {
        return 'oauth.'.$provider.'.state.'.hash('sha256', $state);
    }

    private function assertValidProvider(string $provider): void
    {
        if (! in_array($provider, self::VALID_PROVIDERS, true)) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }
}
