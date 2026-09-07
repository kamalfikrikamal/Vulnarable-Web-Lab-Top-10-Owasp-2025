<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'users' => [
            ['id' => 1, 'username' => 'alice', 'password' => 'alice123', 'role' => 'user'],
            ['id' => 2, 'username' => 'bob', 'password' => 'bob123', 'role' => 'user'],
            ['id' => 3, 'username' => 'admin', 'password' => 'admin123', 'role' => 'admin'],
        ],
        'secret' => "SYSTEM CONFIG (admin-only):\nDATABASE_PASSWORD=Pr0d_db_S3cret!\nSTRIPE_API_KEY=sk_live_51H8x...redacted\nADMIN_2FA_BACKUP_CODE=118422",
    ];
}

function load_db() {
    $path = db_path();
    if (!file_exists($path)) save_db(seed_db());
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : seed_db();
}

function save_db($db) { file_put_contents(db_path(), json_encode($db, JSON_PRETTY_PRINT)); }

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
    echo '<div class="error-box">Kamu belum login. <a href="login.php">Login dulu</a> (disarankan sebagai <code>alice</code>, akun biasa - bukan admin) untuk mencoba lab ini.</div>';
}
