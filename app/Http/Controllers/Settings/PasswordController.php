<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class PasswordController extends Controller
{
    public function edit(Request $request)
    {
        return Inertia::render('settings/Password', [
            'hasPassword' => $request->user()->password !== null,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => $user->password !== null ? 'required|current_password' : 'nullable',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->password = $validated['password'];
        $user->save();

        return back()->with('success', 'Password saved');
    }
}
