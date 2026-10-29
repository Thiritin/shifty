<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Shift;
use App\Models\User;
use App\Support\ShiftCandidates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftCandidatesTest extends TestCase
{
    use RefreshDatabase;

    private function shift(string $date = '2026-08-20', string $start = '10:00', string $end = '12:00', int $people = 1): Shift
    {
        return Shift::create(['name' => "Shift $start", 'date' => $date, 'start_time' => $start, 'end_time' => $end, 'required_people' => $people]);
    }

    private function mark(User $user, array $hours, string $status): void
    {
        foreach ($hours as $hour) {
            Availability::create(['user_id' => $user->id, 'starts_at' => $hour, 'status' => $status]);
        }
    }

    private function giveShifts(User $user, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->shift('2026-09-0'.($i + 1))->users()->attach($user);
        }
    }

    public function test_fewer_shifts_beats_a_better_preference()
    {
        $shift = $this->shift();
        $busyGreen = User::factory()->create(['name' => 'Busy']);
        $idleNeutral = User::factory()->create(['name' => 'Idle']);
        $this->giveShifts($busyGreen, 2);
        $this->mark($busyGreen, ['2026-08-20 10:00:00', '2026-08-20 11:00:00'], 'available');

        $rows = ShiftCandidates::for($shift);

        $this->assertSame(['Idle', 'Busy'], $rows->pluck('name')->all());
        $this->assertTrue($rows[0]['suggested']);
        $this->assertFalse($rows[1]['suggested']);
    }

    public function test_preference_breaks_a_tie_in_shift_count()
    {
        $shift = $this->shift();
        User::factory()->create(['name' => 'Anna']);
        $green = User::factory()->create(['name' => 'Zoe']);
        $this->mark($green, ['2026-08-20 10:00:00', '2026-08-20 11:00:00'], 'available');

        $rows = ShiftCandidates::for($shift);

        $this->assertSame(['Zoe', 'Anna'], $rows->pluck('name')->all());
        $this->assertSame('green', $rows[0]['status']);
        $this->assertSame(2, $rows[0]['available_hours']);
    }

    public function test_hard_conflicts_are_listed_last_and_never_suggested()
    {
        $shift = $this->shift(people: 3);
        $red = User::factory()->create(['name' => 'Red']);
        $this->mark($red, ['2026-08-20 11:00:00'], 'unavailable');
        $away = User::factory()->create(['name' => 'Away', 'arrival_date' => '2026-08-21', 'departure_date' => '2026-08-25']);
        $double = User::factory()->create(['name' => 'Double']);
        $this->shift(start: '11:00', end: '13:00')->users()->attach($double);
        User::factory()->create(['name' => 'Free']);

        $rows = ShiftCandidates::for($shift)->keyBy('name');

        $this->assertSame('Free', ShiftCandidates::for($shift)->first()['name']);
        $this->assertTrue($rows['Red']['blocked']);
        $this->assertSame('no', $rows['Away']['presence']);
        $this->assertTrue($rows['Away']['blocked']);
        $this->assertSame('Shift 11:00', $rows['Double']['conflict']['name']);
        $this->assertFalse($rows['Double']['suggested']);
        $this->assertTrue($rows['Free']['suggested']);
    }

    public function test_assigned_users_are_not_candidates()
    {
        $shift = $this->shift();
        $shift->users()->attach(User::factory()->create(['name' => 'Taken']));
        User::factory()->create(['name' => 'Open']);

        $this->assertSame(['Open'], ShiftCandidates::for($shift)->pluck('name')->all());
    }

    public function test_shifts_crossing_midnight_cover_hours_on_both_days()
    {
        $shift = $this->shift(start: '23:00', end: '01:00');

        $this->assertSame(
            ['2026-08-20 23:00:00', '2026-08-21 00:00:00'],
            ShiftCandidates::hours($shift)->all(),
        );

        $user = User::factory()->create();
        $this->mark($user, ['2026-08-21 00:00:00'], 'unavailable');
        $this->assertSame('red', ShiftCandidates::for($shift)->first()['status']);
    }

    public function test_hourly_marks_are_saved_and_cleared()
    {
        $user = User::factory()->create();
        $this->mark($user, ['2026-08-20 09:00:00'], 'available');

        $this->actingAs($user)->put('/settings/availability/hours', ['cells' => [
            '2026-08-20T09' => null,
            '2026-08-20T10' => 'unavailable',
        ]])->assertRedirect();

        $this->assertSame(['2026-08-20 10:00:00' => 'unavailable'], $user->availabilities()->pluck('status', 'starts_at')->all());

        $this->actingAs($user)->put('/settings/availability/hours', ['cells' => ['2026-08-20T10' => 'available']]);
        $this->assertSame('available', $user->availabilities()->sole()->status);
    }

    public function test_only_admins_see_the_shift_detail_page()
    {
        $shift = $this->shift();

        $this->actingAs(User::factory()->create())->get("/shifts/{$shift->id}")->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get("/shifts/{$shift->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Shifts/Show')->has('candidates', 2));
    }
}
