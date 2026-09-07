<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'users' => [
            ['username' => 'alice', 'balance' => '12,450,000', 'card' => '4111111111111234'],
            ['username' => 'bob', 'balance' => '890,000', 'card' => '5500222233334444'],
        ],
        'referer_log' => [],
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

function current_username() { return $_SESSION['username'] ?? 'alice'; }

function mask_card($card) { return str_repeat('*', strlen($card) - 4) . substr($card, -4); }

function shared_cache_path() { return __DIR__ . '/data/shared_cache.html'; }
