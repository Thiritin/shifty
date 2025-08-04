<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index()
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        $users = User::withCount('shifts')->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'shifts_expected' => $user->shifts_expected,
                    'shift_count' => $user->shifts_count,
                    'dog_mood' => $user->dog_mood,
                    'is_admin' => $user->is_admin,
                ];
            }),
        ]);
    }

    public function update(Request $request, User $user)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'shifts_expected' => 'required|integer|min:0',
            'is_admin' => 'required|boolean',
        ]);

        $user->update($validated);

        return back()->with('success', 'User updated successfully.');
    }
}
