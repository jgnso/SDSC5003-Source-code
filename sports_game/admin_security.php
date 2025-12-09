<?php
if (!function_exists('open_app_db')) {
    function open_app_db()
    {
        static $db = null;
        if ($db === null) {
            $db = new SQLite3(__DIR__ . '/data.db');
        }
        return $db;
    }
}

if (!function_exists('ensure_admin_table')) {
    function ensure_admin_table($db)
    {
        $db->exec('CREATE TABLE IF NOT EXISTS AdminUsers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            is_default INTEGER DEFAULT 0,
            last_login TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
    }
}

if (!function_exists('seed_default_admin')) {
    function seed_default_admin($db)
    {
        $count = (int) $db->querySingle('SELECT COUNT(*) FROM AdminUsers');
        if ($count === 0) {
            $hash = password_hash('12345678', PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO AdminUsers (username, password_hash, is_default) VALUES (:username, :hash, 1)');
            $stmt->bindValue(':username', 'admin', SQLITE3_TEXT);
            $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
            $stmt->execute();
        }
    }
}

if (!function_exists('update_admin_timestamp')) {
    function update_admin_timestamp($db, $id)
    {
        $stmt = $db->prepare('UPDATE AdminUsers SET last_login = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
    }
}
