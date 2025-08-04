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

        // Restrict login to users with claim id RD01V78YL72JLKOZ
        $claims = $socialiteUser->user ?? [];
        $allowedClaimId = 'RD01V78YL72JLKOZ';
        $userClaimIds = [];
        if (isset($claims['groups']) && is_array($claims['groups'])) {
            $userClaimIds = $claims['groups'];
        } elseif (isset($claims['groups'])) {
            $userClaimIds = [$claims['groups']];
        }
        if (!in_array($allowedClaimId, $userClaimIds, true)) {
            return redirect()->route('login')->withErrors(['access' => 'You are not authorized to access this application.']);
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