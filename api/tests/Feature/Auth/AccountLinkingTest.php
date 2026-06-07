<?php

namespace Tests\Feature\Auth;

use App\Models\OauthProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AccountLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_link_a_second_provider(): void
    {
        $user = User::factory()->create();
        OauthProvider::factory()->discord()->for($user)->create([
            'provider_user_id' => 'discord-123',
        ]);

        Sanctum::actingAs($user);

        $state = $this->redirectForLinkState('trackmania', $user);

        $tmUser = $this->makeSocialiteUser([
            'id' => 'tm-uuid-456',
            'name' => 'TMPlayer',
            'nickname' => 'TMPlayer',
        ]);

        $this->mockProvider('trackmania', $tmUser);

        $this->postJson('/api/auth/trackmania/callback', [
            'code' => 'tm-code',
            'state' => $state,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Trackmania account linked successfully.');

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $user->id,
            'provider' => 'trackmania',
            'provider_user_id' => 'tm-uuid-456',
        ]);

        $this->assertDatabaseCount('oauth_providers', 2);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_linking_an_already_linked_own_provider_updates_it(): void
    {
        $user = User::factory()->create();
        OauthProvider::factory()->discord()->for($user)->create([
            'provider_user_id' => 'discord-123',
            'username' => 'oldusername',
        ]);

        Sanctum::actingAs($user);

        $state = $this->redirectForLinkState('discord', $user);

        $discordUser = $this->makeSocialiteUser([
            'id' => 'discord-123',
            'name' => 'Updated Name',
            'nickname' => 'newusername',
        ]);

        $this->mockProvider('discord', $discordUser);

        $this->postJson('/api/auth/discord/callback', [
            'code' => 'discord-code',
            'state' => $state,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Discord account linked successfully.');

        $this->assertDatabaseHas('oauth_providers', [
            'provider_user_id' => 'discord-123',
            'username' => 'newusername',
        ]);
    }

    public function test_linking_a_provider_already_owned_by_another_user_returns_422(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        OauthProvider::factory()->discord()->for($userA)->create([
            'provider_user_id' => 'discord-123',
        ]);
        OauthProvider::factory()->trackmania()->for($userB)->create([
            'provider_user_id' => 'tm-uuid-456',
        ]);

        Sanctum::actingAs($userA);

        $state = $this->redirectForLinkState('trackmania', $userA);

        $tmUser = $this->makeSocialiteUser([
            'id' => 'tm-uuid-456',
            'name' => 'TMPlayer',
            'nickname' => 'TMPlayer',
        ]);

        $this->mockProvider('trackmania', $tmUser);

        $this->postJson('/api/auth/trackmania/callback', [
            'code' => 'tm-code',
            'state' => $state,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('provider');
    }

    public function test_unlink_removes_a_provider_when_another_one_remains(): void
    {
        $user = User::factory()->create();
        OauthProvider::factory()->discord()->for($user)->create();
        OauthProvider::factory()->trackmania()->for($user)->create();

        Sanctum::actingAs($user);

        $this->deleteJson('/api/auth/trackmania/unlink')
            ->assertOk()
            ->assertJsonPath('message', 'Trackmania has been unlinked.');

        $this->assertDatabaseCount('oauth_providers', 1);
        $this->assertDatabaseMissing('oauth_providers', [
            'user_id' => $user->id,
            'provider' => 'trackmania',
        ]);
    }

    public function test_unlink_is_rejected_when_it_is_the_only_provider(): void
    {
        $user = User::factory()->create();
        OauthProvider::factory()->discord()->for($user)->create();

        Sanctum::actingAs($user);

        $this->deleteJson('/api/auth/discord/unlink')
            ->assertStatus(422)
            ->assertJsonValidationErrors('provider');
    }

    public function test_unlink_requires_authentication(): void
    {
        $this->deleteJson('/api/auth/discord/unlink')
            ->assertStatus(401);
    }

    private function redirectForLinkState(string $provider, User $user): string
    {
        $state = null;
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('with')
            ->once()
            ->with(Mockery::on(function (array $parameters) use (&$state): bool {
                $state = $parameters['state'] ?? null;

                return is_string($state) && $state !== '';
            }))
            ->andReturnSelf();
        $mockProvider->shouldReceive('redirect')
            ->once()
            ->andReturnUsing(fn () => new RedirectResponse('https://example.com/oauth?state='.$state));

        $this->mock(SocialiteFactory::class, function (MockInterface $mock) use ($provider, $mockProvider): void {
            $mock->shouldReceive('driver')->with($provider)->andReturn($mockProvider);
        });

        // Use the link endpoint (auth-required) to get a state.
        Sanctum::actingAs($user);
        $response = $this->postJson('/api/auth/'.$provider.'/link');
        $response->assertOk();

        return (string) $state;
    }

    private function mockProvider(string $provider, SocialiteUser $externalUser): void
    {
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('with')->andReturnSelf();
        $mockProvider->shouldReceive('redirect')
            ->andReturn(new RedirectResponse('https://example.com/oauth'))
            ->byDefault();
        $mockProvider->shouldReceive('user')->andReturn($externalUser)->byDefault();

        $this->mock(SocialiteFactory::class, function (MockInterface $mock) use ($provider, $mockProvider): void {
            $mock->shouldReceive('driver')->with($provider)->andReturn($mockProvider);
        });
    }

    private function makeSocialiteUser(array $attributes = []): SocialiteUser
    {
        $attributes = array_merge([
            'id' => 'some-id',
            'name' => 'Test User',
            'nickname' => 'testuser',
            'email' => null,
            'avatar' => null,
        ], $attributes);

        $user = new SocialiteUser;

        return $user->setRaw($attributes)->map([
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'nickname' => $attributes['nickname'],
            'email' => $attributes['email'],
            'avatar' => $attributes['avatar'],
        ]);
    }
}
