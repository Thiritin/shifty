<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AvailabilityController extends Controller
{
    public function edit()
    {
        return Inertia::render('settings/Availability', [
            'user' => Auth::user()
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'arrival_date' => 'nullable|date',
            'departure_date' => 'nullable|date|after_or_equal:arrival_date',
            'arrival_time' => 'nullable|string',
            'departure_time' => 'nullable|string',
        ]);

        /** @var User $user */
        $user = Auth::user();
        foreach ($request->only(['arrival_date', 'departure_date', 'arrival_time', 'departure_time']) as $key => $value) {
            $user->{$key} = $value;
        }
        $user->save();

        return back()->with('success', 'Availability updated successfully.');
    }

    public function completeIntro(Request $request)
    {
        $request->validate([
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after_or_equal:arrival_date',
            'arrival_time' => 'required|string',
            'departure_time' => 'required|string',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->arrival_date = $request->arrival_date;
        $user->departure_date = $request->departure_date;
        $user->arrival_time = $request->arrival_time;
        $user->departure_time = $request->departure_time;
        $user->has_seen_intro = true;
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Welcome to Shifty! Your availability has been set.');
    }
}
