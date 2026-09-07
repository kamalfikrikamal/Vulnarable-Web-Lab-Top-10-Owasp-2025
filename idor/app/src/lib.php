<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'users' => [
            ['id' => 1, 'username' => 'alice', 'password' => 'alice123', 'full_name' => 'Alice Wonderland', 'email' => 'alice@corp.test', 'role' => 'user', 'credit_card' => '4111-2222-3333-4444'],
            ['id' => 2, 'username' => 'bob', 'password' => 'bob123', 'full_name' => 'Bob Builder', 'email' => 'bob@corp.test', 'role' => 'user', 'credit_card' => '5500-1111-2222-3333'],
            ['id' => 3, 'username' => 'carol', 'password' => 'carol123', 'full_name' => 'Carol Danvers', 'email' => 'carol@corp.test', 'role' => 'user', 'credit_card' => '4000-5555-6666-7777'],
            ['id' => 4, 'username' => 'admin', 'password' => 'admin123', 'full_name' => 'Site Administrator', 'email' => 'admin@corp.test', 'role' => 'admin', 'credit_card' => '0000-0000-0000-0000'],
        ],
        'invoices' => [
            ['id' => 5001, 'user_id' => 1, 'item' => 'Annual subscription', 'amount' => 199.00],
            ['id' => 5002, 'user_id' => 2, 'item' => 'Consulting hours', 'amount' => 850.00],
            ['id' => 5003, 'user_id' => 3, 'item' => 'Premium support', 'amount' => 499.00],
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

function reset_db() { save_db(seed_db()); return seed_db(); }

function find_user_by_id($db, $id) {
    foreach ($db['users'] as $u) if ((string)$u['id'] === (string)$id) return $u;
    return null;
}

function find_user_by_username($db, $username) {
    foreach ($db['users'] as $u) if (strcasecmp($u['username'], (string)$username) === 0) return $u;
    return null;
}

function current_user($db) {
    if (empty($_SESSION['uid'])) return null;
    return find_user_by_id($db, $_SESSION['uid']);
}

function require_login_notice() {
    echo '<div class="error-box">Kamu belum login. <a href="login.php">Login dulu</a> untuk mencoba lab ini (login TIDAK mencegah IDOR di lab ini - fungsinya cuma mensimulasikan "kamu adalah user tertentu").</div>';
}
