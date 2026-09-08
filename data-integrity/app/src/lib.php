<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'users' => [
            ['id' => 1, 'username' => 'alice', 'role' => 'user'],
            ['id' => 2, 'username' => 'admin', 'role' => 'admin'],
        ],
        'applied_updates' => [],
    ];
}

function load_db() {
    $path = db_path();
    if (!file_exists($path)) save_db(seed_db());
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : seed_db();
}

function save_db($db) { file_put_contents(db_path(), json_encode($db, JSON_PRETTY_PRINT)); }
