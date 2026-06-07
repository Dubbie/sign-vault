<?php

namespace App\Providers;

use App\Services\TrackmaniaProvider;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Socialite\Contracts\Factory;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        SocialiteWasCalled::class => [
            DiscordExtendSocialite::class.'@handle',
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        // Register the custom Trackmania Socialite provider.
        $socialite = $this->app->make(Factory::class);
        $socialite->extend(TrackmaniaProvider::IDENTIFIER, function ($app) use ($socialite): TrackmaniaProvider {
            $config = $app['config']['services.trackmania'];

            return $socialite->buildProvider(TrackmaniaProvider::class, $config);
        });
    }
}
