<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DiscordCallbackRequest;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\User as DiscordUser;
use Symfony\Component\HttpFoundation\Response;

class DiscordAuthController extends Controller
{
    private const DISCORD_STATE_TTL_MINUTES = 10;

    public function redirect(SocialiteFactory $socialite, CacheRepository $cache): JsonResponse
    {
        $state = Str::random(64);

        // The frontend starts the flow, so we manage OAuth state explicitly.
        $cache->put(
            $this->discordStateCacheKey($state),
            true,
            now()->addMinutes(self::DISCORD_STATE_TTL_MINUTES)
        );

        $url = $socialite->driver('discord')
            ->stateless()
            ->with([
                'state' => $state,
            ])
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $url,
            'state' => $state,
        ]);
    }

    public function callback(
        DiscordCallbackRequest $request,
        SocialiteFactory $socialite,
        CacheRepository $cache
    ): JsonResponse {
        $validated = $request->validated();

        // Consume the state before exchanging the code to block replay.
        if (! $cache->pull($this->discordStateCacheKey($validated['state']))) {
            throw ValidationException::withMessages([
                'state' => ['The OAuth state is invalid or expired.'],
            ]);
        }

        $discordUser = $socialite->driver('discord')
            ->stateless()
            ->user();

        $user = User::updateOrCreate(
            ['discord_id' => (string) $discordUser->getId()],
            $this->discordUserAttributes($discordUser)
        );

        if ($user->isBanned()) {
            return response()->json([
                'message' => 'Your account has been banned.',
                'ban_reason' => $user->ban_reason,
            ], Response::HTTP_FORBIDDEN);
        }

        $token = $user->createToken('discord');

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $this->userResponse($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isBanned()) {
            return response()->json([
                'banned' => true,
                'ban_reason' => $user->ban_reason,
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'user' => $this->userResponse($user),
            'limits' => [
                'sign_upload_max_files' => config('signs.max_upload_files'),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function discordUserAttributes(DiscordUser $discordUser): array
    {
        return [
            'discord_username' => $discordUser->getName() ?? (string) $discordUser->getNickname(),
            'discord_global_name' => data_get($discordUser->getRaw(), 'global_name'),
            'discord_avatar' => $discordUser->getAvatar(),
            'email' => $discordUser->getEmail(),
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function userResponse(User $user): array
    {
        return $user->only([
            'id',
            'discord_id',
            'discord_username',
            'discord_global_name',
            'discord_avatar',
            'email',
            'is_admin',
        ]);
    }

    private function discordStateCacheKey(string $state): string
    {
        return 'oauth.discord.state.'.hash('sha256', $state);
    }
}
