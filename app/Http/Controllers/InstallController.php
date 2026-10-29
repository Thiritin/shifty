<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class InstallController extends Controller
{
    public static function token(): string
    {
        return substr(hash_hmac('sha256', 'shifty-setup', config('app.key')), 0, 20);
    }

    public function show(Request $request)
    {
        abort_if(config('shifty.installed'), 404);

        return Inertia::render('install/Index', [
            'appName' => config('app.name'),
            'token' => AuthController::debugLoginAllowed($request) ? self::token() : null,
        ]);
    }

    public function store(Request $request)
    {
        abort_if(config('shifty.installed'), 404);

        $validated = $request->validate([
            'token' => 'required|string',
            'app_name' => 'required|string|max:60',
            'organization' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! hash_equals(self::token(), $validated['token'])) {
            throw ValidationException::withMessages(['token' => 'This setup token is not valid.']);
        }

        $admin = User::firstOrNew(['email' => $validated['email']]);
        $admin->name = $validated['name'];
        $admin->password = $validated['password'];
        $admin->is_admin = true;
        $admin->save();

        Setting::put([
            'app.name' => $validated['app_name'],
            'shifty.organization' => $validated['organization'] ?? null,
            'shifty.installed' => true,
        ]);

        Auth::login($admin, true);
        $request->session()->regenerate();

        return redirect('/admin/settings');
    }
}
