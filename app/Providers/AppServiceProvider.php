<?php

namespace App\Providers;

use App\Models\User;
use App\Providers\Socialite\SocialiteIdentityProvider;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('self-assign', fn (User $user) => $user->is_admin || config('shifty.self_registration'));

        Socialite::extend('identity', fn ($app) => Socialite::buildProvider(
            SocialiteIdentityProvider::class,
            [...$app['config']['services.identity'], 'redirect' => route('auth.callback')],
        ));
    }
}
