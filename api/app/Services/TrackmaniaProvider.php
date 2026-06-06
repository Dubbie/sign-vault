<?php

namespace App\Services;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class TrackmaniaProvider extends AbstractProvider
{
    public const IDENTIFIER = 'trackmania';

    protected $scopes = [];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.trackmania.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.trackmania.com/api/access_token';
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get('https://api.trackmania.com/api/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data)) {
            throw new \RuntimeException('Trackmania API returned an unexpected response.');
        }

        return $data;
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['accountId'] ?? null,
            'name'     => $user['displayName'] ?? null,
            'nickname' => $user['displayName'] ?? null,
            'email'    => null,
            'avatar'   => null,
        ]);
    }
}
