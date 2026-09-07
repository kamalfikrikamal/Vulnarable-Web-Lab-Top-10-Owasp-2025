<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'users' => [ ['username' => 'admin', 'password' => 'admin123', 'email' => 'admin@corp.test'] ],
        'reset_tokens' => [],   // Lab 1 & 2: token => ['username'=>..,'used'=>bool]
        'reset_codes' => [],    // Lab 3: username => 4-digit code
        'email_log' => [],      // simulated outbound "emails" for Lab 1 & 4
    ];
}

function load_db() {
    $path = db_path();
    if (!file_exists($path)) save_db(seed_db());
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : seed_db();
}

function save_db($db) { file_put_contents(db_path(), json_encode($db, JSON_PRETTY_PRINT)); }

function find_user($db, $username) {
    foreach ($db['users'] as $u) if ($u['username'] === $username) return $u;
    return null;
}
