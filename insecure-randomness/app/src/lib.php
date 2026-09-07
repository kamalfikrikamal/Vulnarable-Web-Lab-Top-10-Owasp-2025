<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'users' => [
            ['id' => 1, 'username' => 'alice', 'email' => 'alice@corp.test'],
            ['id' => 2, 'username' => 'admin', 'email' => 'admin@corp.test'],
        ],
        'reset_tokens' => [],
        'api_keys' => [
            ['id' => 1042, 'owner' => 'alice', 'note' => 'Alice personal API key'],
            ['id' => 1043, 'owner' => 'admin', 'note' => 'Admin master API key - full access'],
        ],
        'coupons' => [],
    ];
}

function load_db() {
    $path = db_path();
    if (!file_exists($path)) save_db(seed_db());
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : seed_db();
}

function save_db($db) { file_put_contents(db_path(), json_encode($db, JSON_PRETTY_PRINT)); }
