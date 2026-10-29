<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::withCount('shifts')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'shifts_expected' => $user->shifts_expected,
                'shift_count' => $user->shifts_count,
                'dog_mood' => $user->dog_mood,
                'is_admin' => $user->is_admin,
                'has_password' => $user->password !== null,
                'has_sso' => $user->remote_id !== null,
                'created_at' => $user->created_at,
            ]),
            'defaultShiftsExpected' => (int) config('shifty.default_shifts_expected'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $user = new User($validated);
        $user->password = $validated['password'] ?? null;
        $user->save();

        return back()->with('success', 'User created');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate($this->rules($user));

        if ($user->is($request->user()) && ! $validated['is_admin']) {
            throw ValidationException::withMessages(['is_admin' => 'You cannot remove your own admin access.']);
        }

        $user->fill($validated);
        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }
        $user->save();

        return back()->with('success', 'User updated');
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'string', Password::min(8)],
            'shifts_expected' => 'required|integer|min:0',
            'is_admin' => 'required|boolean',
        ];
    }
}
