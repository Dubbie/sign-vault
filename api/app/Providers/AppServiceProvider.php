<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        RateLimiter::for('folder-unlock', function (Request $request): Limit {
            return Limit::perMinute(5)->by(sprintf(
                '%s|%s',
                (string) $request->ip(),
                (string) $request->route('slug')
            ));
        });

        RateLimiter::for('engagement-tracking', function (Request $request): Limit {
            return Limit::perMinute(60)->by((string) $request->ip());
        });
    }
}
