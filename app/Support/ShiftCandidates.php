<?php

namespace App\Support;

use App\Models\Availability;
use App\Models\Shift;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ShiftCandidates
{
    /**
     * Start of every hour block the shift touches, as `Y-m-d H:00:00`.
     *
     * @return Collection<int, string>
     */
    public static function hours(Shift $shift): Collection
    {
        $period = CarbonPeriod::create($shift->startsAt()->startOfHour(), '1 hour', $shift->endsAt()->subSecond());

        return collect($period)->map(fn (CarbonInterface $hour) => $hour->format('Y-m-d H:00:00'));
    }

    /**
     * @param  Collection<int, string>  $marks  statuses the user set for the shift's hours
     */
    public static function status(Collection $marks, int $hours): string
    {
        return match (true) {
            $marks->contains('unavailable') => 'red',
            $marks->count() === $hours => 'green',
            default => 'neutral',
        };
    }

    /**
     * Unassigned users, ranked: fewest shifts first, then green before neutral, then name.
     * Users with a hard conflict come last and are never suggested.
     */
    public static function for(Shift $shift): Collection
    {
        $start = $shift->startsAt();
        $end = $shift->endsAt();
        $hours = self::hours($shift);

        $users = User::withCount('shifts')
            ->whereNotIn('id', $shift->users()->pluck('users.id'))
            ->get();

        $marks = Availability::whereIn('user_id', $users->modelKeys())
            ->whereBetween('starts_at', [$hours->first(), $hours->last()])
            ->get()
            ->groupBy('user_id');

        $conflicts = [];
        Shift::with('users:id')
            ->whereKeyNot($shift->getKey())
            ->whereBetween('date', [$start->copy()->subDay()->toDateString(), $end->toDateString()])
            ->get()
            ->filter(fn (Shift $other) => $other->startsAt() < $end && $other->endsAt() > $start)
            ->each(function (Shift $other) use (&$conflicts) {
                foreach ($other->users as $user) {
                    $conflicts[$user->id] ??= ['id' => $other->id, 'name' => $other->name];
                }
            });

        $rows = $users->map(function (User $user) use ($marks, $hours, $conflicts, $start, $end) {
            $statuses = ($marks[$user->id] ?? collect())->pluck('status');
            $status = self::status($statuses, $hours->count());
            $presence = self::presence($user, $start, $end);
            $conflict = $conflicts[$user->id] ?? null;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'shift_count' => $user->shifts_count,
                'shifts_expected' => $user->shifts_expected,
                'status' => $status,
                'available_hours' => $statuses->filter(fn ($s) => $s === 'available')->count(),
                'total_hours' => $hours->count(),
                'presence' => $presence,
                'conflict' => $conflict,
                'blocked' => $conflict !== null || $status === 'red' || $presence === 'no',
                'suggested' => false,
            ];
        });

        [$blocked, $ranked] = $rows->partition('blocked');
        $open = max(0, $shift->required_people - $shift->users()->count());

        return $ranked
            ->sortBy([
                ['shift_count', 'asc'],
                fn ($a, $b) => ($a['status'] === 'green' ? 0 : 1) <=> ($b['status'] === 'green' ? 0 : 1),
                ['name', 'asc'],
            ])
            ->values()
            ->map(fn (array $row, int $i) => [...$row, 'suggested' => $i < $open])
            ->concat($blocked->sortBy('name')->values());
    }

    private static function presence(User $user, CarbonInterface $start, CarbonInterface $end): string
    {
        $from = $user->arrivesAt();
        $to = $user->departsAt();

        if (! $from && ! $to) {
            return 'unknown';
        }

        $from ??= $start;
        $to ??= $end;

        return match (true) {
            $start >= $from && $end <= $to => 'yes',
            $end <= $from || $start >= $to => 'no',
            default => 'partly',
        };
    }
}
