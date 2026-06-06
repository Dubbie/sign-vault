<?php

namespace App\Services;

use App\Models\OauthProvider;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\User as SocialiteUser;

class OauthIdentityService
{
    /**
     * Find or create the local user for a given provider login.
     * Does NOT check ban status or issue tokens — caller handles those.
     */
    public function login(string $provider, SocialiteUser $externalUser): User
    {
        $oauthProvider = OauthProvider::where('provider', $provider)
            ->where('provider_user_id', (string) $externalUser->getId())
            ->first();

        if ($oauthProvider) {
            $user = $oauthProvider->user;
            $oauthProvider->update($this->providerAttributes($externalUser));

            return $user;
        }

        $attrs = $this->providerAttributes($externalUser);

        $user = User::create([
            'display_name' => $attrs['display_name'] ?? $attrs['username'],
            'avatar_url'   => $attrs['avatar_url'],
            'email'        => $attrs['email'],
        ]);

        $user->oauthProviders()->create(array_merge(['provider' => $provider], $attrs));

        return $user;
    }

    /**
     * Link a provider to an existing user, or refresh data if already linked.
     *
     * @throws ValidationException if the provider is already linked to a different account
     */
    public function link(User $currentUser, string $provider, SocialiteUser $externalUser): User
    {
        $existing = OauthProvider::where('provider', $provider)
            ->where('provider_user_id', (string) $externalUser->getId())
            ->first();

        if ($existing && $existing->user_id !== $currentUser->id) {
            throw ValidationException::withMessages([
                'provider' => ['This '.ucfirst($provider).' account is already linked to another user.'],
            ]);
        }

        $attrs = $this->providerAttributes($externalUser);

        if ($existing) {
            $existing->update($attrs);
        } else {
            $currentUser->oauthProviders()->create(array_merge(['provider' => $provider], $attrs));

            if ($currentUser->display_name === null) {
                $currentUser->display_name = $attrs['display_name'] ?? $attrs['username'];
            }
            if ($currentUser->avatar_url === null) {
                $currentUser->avatar_url = $attrs['avatar_url'];
            }
            $currentUser->save();
        }

        return $currentUser->fresh();
    }

    /** @return array<string, string|null> */
    private function providerAttributes(SocialiteUser $externalUser): array
    {
        return [
            'provider_user_id' => (string) $externalUser->getId(),
            'username'         => (string) ($externalUser->getNickname() ?? $externalUser->getName()),
            'display_name'     => $externalUser->getName(),
            'avatar_url'       => $externalUser->getAvatar(),
            'email'            => $externalUser->getEmail(),
        ];
    }
}
