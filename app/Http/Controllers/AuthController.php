<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ClaimMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public static function ssoReady(): bool
    {
        return config('shifty.sso.enabled')
            && filled(config('services.identity.openid_configuration'))
            && filled(config('services.identity.client_id'));
    }

    public static function debugLoginAllowed(Request $request): bool
    {
        $loopback = fn (?string $ip) => in_array(trim((string) $ip), ['127.0.0.1', '::1'], true);
        $forwarded = array_filter(explode(',', (string) $request->header('X-Forwarded-For')));

        return app()->environment('local')
            && config('app.debug')
            && $loopback($request->server('REMOTE_ADDR'))
            && collect($forwarded)->every($loopback);
    }

    public function show(Request $request)
    {
        $debug = self::debugLoginAllowed($request);

        return Inertia::render('auth/Login', [
            'passwordLogin' => (bool) config('shifty.password_login'),
            'sso' => self::ssoReady() ? ['label' => config('shifty.sso.label')] : null,
            'debugUsers' => $debug
                ? User::orderByDesc('is_admin')->orderBy('name')->get(['id', 'name', 'email', 'is_admin'])
                : null,
        ]);
    }

    public function login(Request $request)
    {
        abort_unless(config('shifty.password_login'), 404);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function debugLogin(Request $request)
    {
        abort_unless(self::debugLoginAllowed($request), 404);

        $user = $request->filled('user_id')
            ? User::findOrFail($request->integer('user_id'))
            : User::firstOrCreate(['email' => 'admin@localhost'], ['name' => 'Debug Admin', 'is_admin' => true]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function redirectToProvider()
    {
        abort_unless(self::ssoReady(), 404);

        return Socialite::driver('identity')
            ->setScopes(preg_split('/[\s,]+/', config('shifty.sso.scopes'), -1, PREG_SPLIT_NO_EMPTY))
            ->redirect();
    }

    public function callback()
    {
        abort_unless(self::ssoReady(), 404);

        try {
            $identity = Socialite::driver('identity')->user();
        } catch (Throwable $e) {
            report($e);

            return $this->ssoError('Sign-in failed. Please try again.');
        }

        $sso = config('shifty.sso');
        $claims = $identity->getRaw();

        if (filled($sso['access_claim']) && filled($sso['access_value'])
            && ! ClaimMatcher::matches($claims, $sso['access_claim'], $sso['access_value'])) {
            return $this->ssoError('Your account does not have access to this app.');
        }

        if (blank($identity->getEmail())) {
            return $this->ssoError('Your account has no email address.');
        }

        $user = $this->findOrCreateUser($identity, $sso);
        if (is_string($user)) {
            return $this->ssoError($user);
        }

        if (filled($sso['admin_claim']) && filled($sso['admin_value'])
            && ClaimMatcher::matches($claims, $sso['admin_claim'], $sso['admin_value'])) {
            $user->is_admin = true;
        }

        $user->save();
        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Returns an error message instead of a user when the login must be refused.
     */
    private function findOrCreateUser(SocialiteUser $identity, array $sso): User|string
    {
        $email = $identity->getEmail();
        $user = User::where('remote_id', $identity->getId())->first();

        if (! $user && $sso['link_by_email']) {
            $user = User::where('email', $email)->whereNull('remote_id')->first();
        }

        if (! $user) {
            if (! $sso['auto_create']) {
                return 'No account exists for you yet. Ask an admin to add you.';
            }
            if (User::where('email', $email)->exists()) {
                return 'An account with this email address already exists.';
            }
            $user = new User(['shifts_expected' => config('shifty.default_shifts_expected')]);
        }

        $user->remote_id = $identity->getId();
        $user->name = $identity->getName() ?: ($user->name ?: $email);
        if (! User::where('email', $email)->whereKeyNot($user->getKey())->exists()) {
            $user->email = $email;
        }

        return $user;
    }

    private function ssoError(string $message)
    {
        return redirect()->route('login')->withErrors(['sso' => $message]);
    }
}
