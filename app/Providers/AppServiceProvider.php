<?php

namespace App\Providers;

use App\Providers\Socialite\SocialiteIdentityProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

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
        Socialite::extend('identity', function ($app) {
            $config = $app['config']['services.identity'];
            return Socialite::buildProvider(SocialiteIdentityProvider::class, $config);
        });
    }
}
