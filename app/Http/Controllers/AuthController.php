<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login()
    {
        return Socialite::driver('identity')
            ->scopes(['openid', 'profile', 'email', 'groups'])
            ->redirect();
    }

    public function loginCallback()
    {
        try {
            $socialiteUser = Socialite::driver('identity')->user();
        } catch (\Exception $e) {
            return redirect()->route('login');
        }

        $user = User::updateOrCreate([
            'remote_id' => $socialiteUser->getId(),
        ], [
            'remote_id' => $socialiteUser->getId(),
            'name' => $socialiteUser->getName(),
            'email' => $socialiteUser->getEmail(),
            'shifts_expected' => config('shifty.default_shifts_expected'),
        ]);

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('welcome');
    }
}