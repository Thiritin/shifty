<?php

namespace App\Providers;

use App\Models\User;
use App\Providers\Socialite\SocialiteIdentityProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // Behind a TLS-terminating proxy the request looks like plain HTTP; APP_URL is the source of truth.
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::define('self-assign', fn (User $user) => $user->is_admin || config('shifty.self_registration'));

        Socialite::extend('identity', fn ($app) => Socialite::buildProvider(
            SocialiteIdentityProvider::class,
            [...$app['config']['services.identity'], 'redirect' => route('auth.callback')],
        ));
    }
}
