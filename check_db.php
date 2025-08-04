#!/usr/bin/env php
<?php

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "Users table schema:\n";
$stmt = $db->query("PRAGMA table_info(users)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- {$row['name']} ({$row['type']})\n";
}

echo "\nAll tables:\n";
$stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- {$row['name']}\n";
}
