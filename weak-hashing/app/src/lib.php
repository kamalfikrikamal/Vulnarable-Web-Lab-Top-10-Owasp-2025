<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'users_plaintext' => [
            ['id' => 1, 'username' => 'alice', 'password' => 'Summer2024!'],
            ['id' => 2, 'username' => 'bob', 'password' => 'P@ssw0rd'],
            ['id' => 3, 'username' => 'admin', 'password' => 'admin123'],
        ],
        // Same passwords, hashed with a fast, unsalted algorithm - the
        // classic anti-pattern this lab demonstrates.
        'users_md5' => [
            ['id' => 1, 'username' => 'alice', 'hash' => md5('Summer2024!')],
            ['id' => 2, 'username' => 'bob', 'hash' => md5('P@ssw0rd')],
            ['id' => 3, 'username' => 'admin', 'hash' => md5('admin123')],
        ],
        'remember_me_cookie' => null,
    ];
}

function load_db() {
    $path = db_path();
    if (!file_exists($path)) save_db(seed_db());
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : seed_db();
}

function save_db($db) { file_put_contents(db_path(), json_encode($db, JSON_PRETTY_PRINT)); }
