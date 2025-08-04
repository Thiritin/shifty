#!/usr/bin/env php
<?php

// Simple seeder that bypasses Laravel's bootstrap process

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "Running seeders...\n";

// Check if we already have test data
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE email = 'admin@example.com'");
if ($stmt->fetchColumn() > 0) {
    echo "ℹ Test data already exists\n";
    exit(0);
}

// Create test users
$users = [
    ['name' => 'Admin User', 'email' => 'admin@example.com', 'remote_id' => 'admin123', 'shifts_expected' => 5, 'is_admin' => 1],
    ['name' => 'Alice Smith', 'email' => 'alice@example.com', 'remote_id' => 'alice_smith', 'shifts_expected' => 3, 'is_admin' => 0],
    ['name' => 'Bob Johnson', 'email' => 'bob@example.com', 'remote_id' => 'bob_johnson', 'shifts_expected' => 4, 'is_admin' => 0],
    ['name' => 'Carol Williams', 'email' => 'carol@example.com', 'remote_id' => 'carol_williams', 'shifts_expected' => 2, 'is_admin' => 0],
    ['name' => 'David Brown', 'email' => 'david@example.com', 'remote_id' => 'david_brown', 'shifts_expected' => 3, 'is_admin' => 0],
];

$stmt = $db->prepare("
    INSERT INTO users (name, email, password, remote_id, shifts_expected, is_admin, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
");

$hashedPassword = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // bcrypt for 'password'

foreach ($users as $user) {
    $stmt->execute([$user['name'], $user['email'], $hashedPassword, $user['remote_id'], $user['shifts_expected'], $user['is_admin']]);
}

echo "✓ Created " . count($users) . " test users\n";

// Create test shifts for current week
$startDate = new DateTime();
$startDate->modify('last monday');
if ($startDate->format('N') != 1) {
    $startDate->modify('last monday');
}

$shiftTemplates = [
    ['name' => 'Morning Shift', 'start_time' => '08:00', 'end_time' => '16:00', 'required_people' => 2],
    ['name' => 'Afternoon Shift', 'start_time' => '12:00', 'end_time' => '20:00', 'required_people' => 2],
    ['name' => 'Evening Shift', 'start_time' => '16:00', 'end_time' => '00:00', 'required_people' => 1],
];

$shiftStmt = $db->prepare("
    INSERT INTO shifts (name, date, start_time, end_time, required_people, description, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
");

$shiftCount = 0;

// Create shifts for this week (Monday to Friday)
for ($day = 0; $day < 5; $day++) {
    $date = clone $startDate;
    $date->modify("+{$day} days");
    
    foreach ($shiftTemplates as $template) {
        // Skip some shifts to make it more realistic
        if ($day === 0 && $template['name'] === 'Evening Shift') continue; // No evening shift on Monday
        if ($day === 4 && $template['name'] === 'Evening Shift') continue; // No evening shift on Friday
        
        $shiftStmt->execute([
            $template['name'],
            $date->format('Y-m-d'),
            $template['start_time'],
            $template['end_time'],
            $template['required_people'],
            'Standard ' . strtolower($template['name']) . ' coverage'
        ]);
        
        $shiftCount++;
    }
}

echo "✓ Created {$shiftCount} test shifts\n";

// Assign some users to shifts randomly
$shifts = $db->query("SELECT id, required_people FROM shifts")->fetchAll(PDO::FETCH_ASSOC);
$userIds = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);

$assignStmt = $db->prepare("
    INSERT OR IGNORE INTO shift_user (shift_id, user_id, created_at, updated_at) 
    VALUES (?, ?, datetime('now'), datetime('now'))
");

$assignmentCount = 0;

foreach ($shifts as $shift) {
    // Assign random number of users (70% chance of assignment)
    if (rand(1, 100) <= 70) {
        $numToAssign = rand(1, min($shift['required_people'], count($userIds)));
        $assignedUsers = array_rand(array_flip($userIds), $numToAssign);
        
        if (!is_array($assignedUsers)) {
            $assignedUsers = [$assignedUsers];
        }
        
        foreach ($assignedUsers as $userId) {
            $assignStmt->execute([$shift['id'], $userId]);
            $assignmentCount++;
        }
    }
}

echo "✓ Created {$assignmentCount} shift assignments\n";
echo "Seeding completed!\n";
