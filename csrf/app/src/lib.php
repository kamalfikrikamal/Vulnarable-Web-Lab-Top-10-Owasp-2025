<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'user' => ['username' => 'victim', 'email' => 'victim@corp.test'],
        'valid_tokens' => [],
    ];
}

function load_db() {
    $path = db_path();
    if (!file_exists($path)) save_db(seed_db());
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : seed_db();
}

function save_db($db) { file_put_contents(db_path(), json_encode($db, JSON_PRETTY_PRINT)); }

// Every lab in this app treats the browser as already logged in as
// "victim" once /login.php has been visited once (PHP session cookie),
// simulating that the participant is a logged-in user visiting an
// attacker-controlled page in another tab.
function is_logged_in() { return !empty($_SESSION['logged_in']); }
