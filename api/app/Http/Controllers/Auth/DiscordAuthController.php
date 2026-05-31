<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DiscordCallbackRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\User as DiscordUser;

class DiscordAuthController extends Controller
{
    public function redirect(SocialiteFactory $socialite): JsonResponse
    {
        $url = $socialite->driver('discord')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $url,
        ]);
    }

    public function callback(
        DiscordCallbackRequest $request,
        SocialiteFactory $socialite
    ): JsonResponse {
        $discordUser = $socialite->driver('discord')
            ->stateless()
            ->user();

        $user = User::updateOrCreate(
            ['discord_id' => (string) $discordUser->getId()],
            $this->discordUserAttributes($discordUser)
        );

        $token = $user->createToken('discord');

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $this->userResponse($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userResponse($request->user()),
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
        ]);
    }
}
