<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'remote_id' => 'admin123',
            'shifts_expected' => 5,
            'is_admin' => true,
        ]);

        // Create some regular users
        $users = [
            ['name' => 'Alice Smith', 'email' => 'alice@example.com', 'shifts_expected' => 3],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.com', 'shifts_expected' => 4],
            ['name' => 'Carol Williams', 'email' => 'carol@example.com', 'shifts_expected' => 2],
            ['name' => 'David Brown', 'email' => 'david@example.com', 'shifts_expected' => 3],
        ];

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'remote_id' => str_replace(' ', '_', strtolower($userData['name'])),
                'shifts_expected' => $userData['shifts_expected'],
                'is_admin' => false,
            ]);
        }

        // Create shifts for this week and next week
        $startDate = Carbon::now()->startOfWeek();
        
        $shiftTemplates = [
            ['name' => 'Morning Shift', 'start_time' => '08:00', 'end_time' => '16:00', 'required_people' => 2],
            ['name' => 'Afternoon Shift', 'start_time' => '12:00', 'end_time' => '20:00', 'required_people' => 2],
            ['name' => 'Evening Shift', 'start_time' => '16:00', 'end_time' => '00:00', 'required_people' => 1],
            ['name' => 'Night Shift', 'start_time' => '20:00', 'end_time' => '08:00', 'required_people' => 1],
        ];

        // Create shifts for current week and next week
        for ($week = 0; $week < 2; $week++) {
            for ($day = 0; $day < 7; $day++) {
                $date = $startDate->copy()->addWeeks($week)->addDays($day);
                
                // Skip weekends for some shifts
                if ($date->isWeekend()) {
                    continue;
                }

                foreach ($shiftTemplates as $index => $template) {
                    // Only create some shifts to make it more realistic
                    if ($day === 0 && $index > 1) continue; // Monday only morning/afternoon
                    if ($day === 4 && $index > 2) continue; // Friday only morning/afternoon/evening
                    
                    $shift = Shift::create([
                        'name' => $template['name'],
                        'date' => $date->format('Y-m-d'),
                        'start_time' => $template['start_time'],
                        'end_time' => $template['end_time'],
                        'required_people' => $template['required_people'],
                        'description' => 'Standard ' . strtolower($template['name']) . ' coverage',
                    ]);

                    // Randomly assign some users to shifts (about 70% coverage)
                    if (random_int(1, 100) <= 70) {
                        $allUsers = User::all();
                        $usersToAssign = $allUsers->random(min($template['required_people'], random_int(1, $template['required_people'])));
                        
                        foreach ($usersToAssign as $user) {
                            $shift->users()->attach($user);
                        }
                    }
                }
            }
        }
    }
}
