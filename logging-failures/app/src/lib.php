<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'users' => [
            ['id' => 1, 'username' => 'alice', 'email' => 'alice@corp.test'],
            ['id' => 2, 'username' => 'admin', 'email' => 'admin@corp.test'],
        ],
    ];
}

function load_db() {
    $path = db_path();
    if (!file_exists($path)) save_db(seed_db());
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : seed_db();
}

function save_db($db) { file_put_contents(db_path(), json_encode($db, JSON_PRETTY_PRINT)); }

// --- helper log file paths, dipakai lintas lab ---
function log_path($name) { return __DIR__ . '/data/' . $name; }

function append_log($name, $line) {
    file_put_contents(log_path($name), $line . "\n", FILE_APPEND);
}

function read_log($name) {
    $path = log_path($name);
    if (!file_exists($path)) return '';
    return file_get_contents($path);
}
