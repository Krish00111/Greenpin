<?php

$dbFile = __DIR__ . '/database.sqlite';
if (file_exists($dbFile)) {
    echo "Database already exists at $dbFile\n";
    exit;
}

try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $db->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");


    $db->exec("
        CREATE TABLE pins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            lat REAL NOT NULL,
            lng REAL NOT NULL,
            crop_suggestion TEXT,
            metadata TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id)
        );
    ");

    echo "Database and tables created successfully at $dbFile\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
