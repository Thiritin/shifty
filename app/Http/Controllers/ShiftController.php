<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        
        // Get all shifts stats (not just current week since EF is only one week)
        $shifts = Shift::with('users')->get();
        
        $totalShifts = $shifts->count();
        $totalSpots = $shifts->sum('required_people');
        $filledSpots = $shifts->sum('assigned_count');
        $unfilledShifts = $shifts->filter(function ($shift) {
            return $shift->spots_available > 0;
        });

        // Get user's assigned shifts
        $userShifts = Shift::whereHas('users', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('users')->orderBy('date')->orderBy('start_time')->get()->map(function ($shift) use ($user) {
            return [
                'id' => $shift->id,
                'name' => $shift->name,
                'description' => $shift->description,
                'date' => $shift->date->format('Y-m-d'), // Ensure consistent date format
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'required_people' => $shift->required_people,
                'assigned_count' => $shift->assigned_count,
            ];
        });

        return Inertia::render('Dashboard', [
            'shiftStats' => [
                'totalShifts' => $totalShifts,
                'totalSpots' => $totalSpots,
                'filledSpots' => $filledSpots,
                'unfilledShifts' => $unfilledShifts->values(),
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'shift_count' => Shift::whereHas('users', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->count(),
                'shifts_expected' => $user->shifts_expected,
                'dog_mood' => $user->dog_mood,
                'has_seen_intro' => $user->has_seen_intro,
                'arrival_date' => $user->arrival_date,
                'departure_date' => $user->departure_date,
                'arrival_time' => $user->arrival_time,
                'departure_time' => $user->departure_time,
            ],
            'userShifts' => $userShifts,
        ]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get all shifts (no week filtering, only show days with shifts)
        $shifts = Shift::with('users')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($shift) use ($user) {
                return [
                    'id' => $shift->id,
                    'name' => $shift->name,
                    'description' => $shift->description,
                    'date' => $shift->date,
                    'start_time' => $shift->start_time,
                    'end_time' => $shift->end_time,
                    'required_people' => $shift->required_people,
                    'assigned_count' => $shift->assigned_count,
                    'is_assigned' => $shift->users->contains('id', $user->id),
                    'is_full' => $shift->assigned_count >= $shift->required_people,
                    'users' => $shift->users->map(function ($u) {
                        return [
                            'id' => $u->id,
                            'name' => $u->name,
                        ];
                    }),
                ];
            });

        $allUsers = User::all()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
            ];
        });

        return inertia('Shifts/Index', [
            'shifts' => $shifts,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'shift_count' => Shift::whereHas('users', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->count(),
                'shifts_expected' => $user->shifts_expected,
                'dog_mood' => $user->dog_mood,
            ],
            'allUsers' => $allUsers,
        ]);
    }

    public function assign(Request $request, Shift $shift)
    {
        $user = Auth::user();

        if ($shift->is_full) {
            return back()->with('error', 'This shift is already full.');
        }

        if ($shift->users->contains($user)) {
            return back()->with('error', 'You are already assigned to this shift.');
        }

        $shift->users()->attach($user);

        return back()->with('success', 'You have been assigned to this shift.');
    }

    public function unassign(Request $request, Shift $shift)
    {
        $user = Auth::user();

        if (!$shift->users->contains($user)) {
            return back()->with('error', 'You are not assigned to this shift.');
        }

        $shift->users()->detach($user);

        return back()->with('success', 'You have been removed from this shift.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'required_people' => 'required|integer|min:1',
        ]);

        Shift::create($validated);

        return redirect()->route('shifts.index')->with('message', 'Shift created successfully.');
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'required_people' => 'required|integer|min:1',
        ]);

        $shift->update($validated);

        return redirect()->route('shifts.index')->with('message', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();

        return redirect()->route('shifts.index')->with('message', 'Shift deleted successfully.');
    }

    public function adminAssign(Request $request, Shift $shift, User $user)
    {
        if ($shift->users->count() >= $shift->required_people) {
            return redirect()->route('shifts.index')->with('error', 'Shift is already full.');
        }

        if ($shift->users->contains($user)) {
            return redirect()->route('shifts.index')->with('error', 'User is already assigned to this shift.');
        }

        $shift->users()->attach($user);

        return redirect()->route('shifts.index')->with('message', 'User assigned to shift successfully.');
    }

    public function adminUnassign(Shift $shift, User $user)
    {
        $shift->users()->detach($user);

        return redirect()->route('shifts.index')->with('message', 'User removed from shift successfully.');
    }

    public function print(Request $request)
    {
        $shifts = Shift::with('users')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('date');

        return view('shifts.print', compact('shifts'));
    }

    public function calendar(Request $request)
    {
        $user = Auth::user();
        
        // Generate calendar for user's shifts
        $shifts = Shift::whereHas('users', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->orderBy('date')->orderBy('start_time')->get();
        
        $icsContent = $this->generateICS($shifts, $user);
        
        return response($icsContent)
            ->header('Content-Type', 'text/calendar')
            ->header('Content-Disposition', 'attachment; filename="badge_duty_shifts.ics"');
    }

    private function generateICS($shifts, $user)
    {
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Eurofurence//Badge Duty Shifts//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        
        foreach ($shifts as $shift) {
            $startDateTime = Carbon::parse($shift->date . ' ' . $shift->start_time);
            $endDateTime = Carbon::parse($shift->date . ' ' . $shift->end_time);
            
            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:" . $shift->id . "@shifty.eurofurence.org\r\n";
            $ics .= "DTSTART:" . $startDateTime->utc()->format('Ymd\THis\Z') . "\r\n";
            $ics .= "DTEND:" . $endDateTime->utc()->format('Ymd\THis\Z') . "\r\n";
            $ics .= "SUMMARY:" . $this->escapeICS($shift->name) . "\r\n";
            $ics .= "DESCRIPTION:" . $this->escapeICS($shift->description ?: 'Badge duty shift for Eurofurence') . "\r\n";
            $ics .= "LOCATION:Badge Distribution Area\r\n";
            $ics .= "STATUS:CONFIRMED\r\n";
            $ics .= "END:VEVENT\r\n";
        }
        
        $ics .= "END:VCALENDAR\r\n";
        
        return $ics;
    }

    private function escapeICS($text)
    {
        return str_replace(["\n", "\r", ",", ";"], ["\\n", "\\r", "\\,", "\\;"], $text);
    }
}
