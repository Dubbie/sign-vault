<?php

namespace Tests\Feature\Auth;

use App\Models\Folder;
use App\Models\Sign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DiscordAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_endpoint_returns_a_discord_auth_url(): void
    {
        $state = null;
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('with')
            ->once()
            ->with(Mockery::on(function (array $parameters) use (&$state): bool {
                $state = $parameters['state'] ?? null;

                return is_string($state) && $state !== '';
            }))
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturnUsing(function () use (&$state): RedirectResponse {
                return new RedirectResponse('https://discord.com/oauth2/authorize?client_id=test-client&scope=identify&state='.$state);
            });

        $this->mock(SocialiteFactory::class, function (MockInterface $mock) use ($provider): void {
            $mock->shouldReceive('driver')
                ->once()
                ->with('discord')
                ->andReturn($provider);
        });

        $this->getJson('/api/auth/discord/redirect')
            ->assertOk()
            ->assertJsonPath('url', 'https://discord.com/oauth2/authorize?client_id=test-client&scope=identify&state='.$state)
            ->assertJsonPath('state', $state);
    }

    public function test_callback_requires_a_code(): void
    {
        $this->postJson('/api/auth/discord/callback')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'state']);
    }

    public function test_callback_creates_a_new_user_and_returns_a_sanctum_token(): void
    {
        $state = $this->redirectForState();
        $discordUser = $this->makeDiscordUser([
            'id' => '123456789',
            'username' => 'exampleuser',
            'name' => 'Example User',
            'avatar' => 'https://cdn.discordapp.com/avatars/123456789/avatarhash.png',
            'email' => null,
        ]);

        $this->mockDiscordProvider($discordUser);

        $response = $this->postJson('/api/auth/discord/callback', [
            'code' => 'discord-code',
            'state' => $state,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'display_name',
                    'avatar_url',
                    'email',
                ],
            ])
            ->assertJsonPath('user.display_name', 'Example User')
            ->assertJsonPath('user.email', null);

        $this->assertDatabaseHas('users', [
            'display_name' => 'Example User',
            'avatar_url' => 'https://cdn.discordapp.com/avatars/123456789/avatarhash.png',
        ]);

        $this->assertDatabaseHas('oauth_providers', [
            'provider' => 'discord',
            'provider_user_id' => '123456789',
            'username' => 'exampleuser',
        ]);
    }

    public function test_callback_rejects_an_unknown_oauth_state(): void
    {
        $this->mockDiscordProvider($this->makeDiscordUser());

        $this->postJson('/api/auth/discord/callback', [
            'code' => 'discord-code',
            'state' => 'unknown-state',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('state');
    }

    public function test_me_endpoint_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create([
            'display_name' => 'Example User',
            'avatar_url' => 'https://cdn.discordapp.com/avatars/123456789/avatarhash.png',
            'email' => 'user@example.com',
        ]);
        $folder = Folder::factory()->for($user)->create();
        Sign::factory()->for($user)->for($folder)->count(2)->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('limits.sign_upload_max_files', config('signs.max_upload_files'))
            ->assertJsonPath('user.display_name', 'Example User')
            ->assertJsonPath('user.email', 'user@example.com')
            ->assertJsonPath('user.folders_count', 1)
            ->assertJsonPath('user.signs_count', 2);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertStatus(401);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('discord');

        $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])
            ->assertOk()
            ->assertJson(['message' => 'Logged out.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_repeated_discord_logins_update_the_existing_provider_record(): void
    {
        $firstState = $this->redirectForState();
        $secondState = $this->redirectForState();

        $firstDiscordUser = $this->makeDiscordUser([
            'id' => '123456789',
            'username' => 'exampleuser',
            'name' => 'Example User',
            'avatar' => 'https://cdn.discordapp.com/avatars/123456789/avatarhash-one.png',
            'email' => 'first@example.com',
        ]);

        $secondDiscordUser = $this->makeDiscordUser([
            'id' => '123456789',
            'username' => 'updateduser',
            'name' => 'Updated User',
            'avatar' => 'https://cdn.discordapp.com/avatars/123456789/avatarhash-two.png',
            'email' => null,
        ]);

        $this->mockDiscordProvider($firstDiscordUser, $secondDiscordUser);

        $this->postJson('/api/auth/discord/callback', [
            'code' => 'discord-code-1',
            'state' => $firstState,
        ])->assertOk();

        $this->postJson('/api/auth/discord/callback', [
            'code' => 'discord-code-2',
            'state' => $secondState,
        ])
            ->assertOk()
            ->assertJsonPath('user.display_name', 'Example User'); // user.display_name is NOT updated on re-login

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('oauth_providers', [
            'provider' => 'discord',
            'provider_user_id' => '123456789',
            'username' => 'updateduser',
        ]);
    }

    private function mockDiscordProvider(SocialiteUser $firstUser, ?SocialiteUser $secondUser = null): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->andReturn(new RedirectResponse('https://discord.com/oauth2/authorize?client_id=test-client'))
            ->byDefault();
        $provider->shouldReceive('user')
            ->andReturn($firstUser, $secondUser ?? $firstUser)
            ->byDefault();

        $this->mock(SocialiteFactory::class, function (MockInterface $mock) use ($provider): void {
            $mock->shouldReceive('driver')
                ->with('discord')
                ->andReturn($provider);
        });
    }

    private function redirectForState(): string
    {
        $redirectUrl = 'https://discord.com/oauth2/authorize?client_id=test-client&scope=identify';
        $state = null;
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('with')
            ->once()
            ->with(Mockery::on(function (array $parameters) use (&$state): bool {
                $state = $parameters['state'] ?? null;

                return is_string($state) && $state !== '';
            }))
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturnUsing(function () use ($redirectUrl, &$state): RedirectResponse {
                return new RedirectResponse($redirectUrl.'&state='.$state);
            });

        $this->mock(SocialiteFactory::class, function (MockInterface $mock) use ($provider): void {
            $mock->shouldReceive('driver')
                ->once()
                ->with('discord')
                ->andReturn($provider);
        });

        $response = $this->getJson('/api/auth/discord/redirect');
        $response->assertOk()->assertJsonPath('state', $state);

        return (string) $state;
    }

    private function makeDiscordUser(array $attributes = []): SocialiteUser
    {
        $attributes = array_merge([
            'id' => '123456789',
            'username' => 'exampleuser',
            'name' => 'Example User',
            'avatar' => 'https://cdn.discordapp.com/avatars/123456789/avatarhash.png',
            'email' => 'user@example.com',
        ], $attributes);

        $user = new SocialiteUser;

        return $user->setRaw($attributes)->map([
            'id' => $attributes['id'],
            'nickname' => $attributes['username'],
            'name' => $attributes['name'],
            'email' => $attributes['email'] ?? null,
            'avatar' => $attributes['avatar'],
        ]);
    }
}
