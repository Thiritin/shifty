<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminShiftController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        $startDate = $request->get('week') 
            ? Carbon::parse($request->get('week'))->startOfWeek()
            : Carbon::now()->startOfWeek();

        $shifts = Shift::forWeek($startDate)
            ->with('users')
            ->get();

        $users = User::all(['id', 'name', 'shifts_expected']);

        return Inertia::render('Admin/Shifts/Index', [
            'shifts' => $shifts,
            'users' => $users,
            'currentWeek' => $startDate->format('Y-m-d'),
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'required_people' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        Shift::create($validated);

        return back()->with('success', 'Shift created successfully.');
    }

    public function update(Request $request, Shift $shift)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'required_people' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $shift->update($validated);

        return back()->with('success', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        $shift->delete();

        return back()->with('success', 'Shift deleted successfully.');
    }

    public function assignUser(Request $request, Shift $shift)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);

        if ($shift->users->contains($user)) {
            return back()->with('error', 'User is already assigned to this shift.');
        }

        if ($shift->is_full) {
            return back()->with('error', 'This shift is already full.');
        }

        $shift->users()->attach($user);

        return back()->with('success', 'User assigned to shift successfully.');
    }

    public function unassignUser(Request $request, Shift $shift, User $user)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        if (!$shift->users->contains($user)) {
            return back()->with('error', 'User is not assigned to this shift.');
        }

        $shift->users()->detach($user);

        return back()->with('success', 'User removed from shift successfully.');
    }
}
