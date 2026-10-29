<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\Shift;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AvailabilityController extends Controller
{
    public function edit()
    {
        /** @var User $user */
        $user = Auth::user();

        return Inertia::render('settings/Availability', [
            'user' => $user,
            'grid' => $this->grid($user),
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

    public function updateHours(Request $request)
    {
        $validated = $request->validate([
            'cells' => 'required|array|max:2000',
            'cells.*' => 'nullable|in:available,unavailable',
        ]);

        $user = $request->user();
        $clear = [];
        $rows = [];
        foreach ($validated['cells'] as $key => $status) {
            abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}$/', $key), 422);
            $startsAt = Carbon::createFromFormat('Y-m-d\TH', $key)->format('Y-m-d H:00:00');

            if ($status === null) {
                $clear[] = $startsAt;
            } else {
                $rows[] = ['user_id' => $user->id, 'starts_at' => $startsAt, 'status' => $status];
            }
        }

        $user->availabilities()->whereIn('starts_at', $clear)->delete();
        Availability::upsert($rows, ['user_id', 'starts_at'], ['status']);

        return back()->with('success', 'Saved');
    }

    /**
     * Days from the first to the last shift, or the user's stay when there are no shifts yet.
     */
    private function grid(User $user): array
    {
        if (config('shifty.event_start') && config('shifty.event_end')) {
            $first = Carbon::parse(config('shifty.event_start'));
            $last = Carbon::parse(config('shifty.event_end'));
        } else {
            $shifts = Shift::all();
            $first = $shifts->map->startsAt()->min() ?? $user->arrival_date;
            $last = $shifts->map(fn (Shift $s) => $s->endsAt()->subSecond())->max() ?? $user->departure_date;
        }

        if (! $first || ! $last) {
            return ['days' => [], 'cells' => (object) [], 'arrivesAt' => null, 'departsAt' => null];
        }

        $days = collect(CarbonPeriod::create($first->copy()->startOfDay(), '1 day', $last->copy()->startOfDay()))
            ->take(31)
            ->map(fn (CarbonInterface $day) => $day->format('Y-m-d'))
            ->values();

        $cells = $user->availabilities()
            ->whereBetween('starts_at', [$days->first().' 00:00:00', $days->last().' 23:00:00'])
            ->get()
            ->mapWithKeys(fn (Availability $a) => [$a->starts_at->format('Y-m-d\TH') => $a->status]);

        return [
            'days' => $days,
            'cells' => (object) $cells->all(),
            'arrivesAt' => $user->arrivesAt()?->format('Y-m-d\TH:i'),
            'departsAt' => $user->departsAt()?->format('Y-m-d\TH:i'),
        ];
    }
}
