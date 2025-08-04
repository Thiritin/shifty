#!/usr/bin/env php
<?php

// Simple migration runner that bypasses Laravel's bootstrap process

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

// Run the migrations
echo "Running migrations...\n";

// Add columns to users table
try {
    $db->exec("ALTER TABLE users ADD COLUMN shifts_expected INTEGER DEFAULT 0");
    $db->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT 0");
    echo "✓ Added shifts_expected and is_admin to users table\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "ℹ Users table already has the new columns\n";
    } else {
        echo "Error updating users table: " . $e->getMessage() . "\n";
    }
}

// Create shifts table
try {
    $db->exec("
        CREATE TABLE shifts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            required_people INTEGER DEFAULT 1,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Created shifts table\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'table shifts already exists') !== false) {
        echo "ℹ Shifts table already exists\n";
    } else {
        echo "Error creating shifts table: " . $e->getMessage() . "\n";
    }
}

// Create shift_user pivot table
try {
    $db->exec("
        CREATE TABLE shift_user (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            shift_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE(shift_id, user_id)
        )
    ");
    echo "✓ Created shift_user table\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'table shift_user already exists') !== false) {
        echo "ℹ Shift_user table already exists\n";
    } else {
        echo "Error creating shift_user table: " . $e->getMessage() . "\n";
    }
}

echo "Migrations completed!\n";
