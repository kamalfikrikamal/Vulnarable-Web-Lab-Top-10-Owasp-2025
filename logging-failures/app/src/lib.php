<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'users' => [
            ['id' => 1, 'username' => 'alice', 'email' => 'alice@corp.test'],
            ['id' => 2, 'username' => 'admin', 'email' => 'admin@corp.test'],
        ],
        // --- Lab 5: threshold evasion ---
        'threshold_login_fails' => [],   // timestamp unix percobaan gagal dalam window aktif
        'threshold_alerts_fired' => 0,   // berapa kali alert benar-benar terpicu
        'threshold_total_attempts' => 0, // total percobaan gagal sepanjang waktu (all-time)
        // --- Lab 6: log tampering oleh pengguna sendiri ---
        'audit_log' => [
            ['id' => 1, 'ts' => '2026-09-01 08:12:03', 'user' => 'budi', 'event' => 'user mengubah alamat email menjadi budi.baru@mail.test'],
            ['id' => 2, 'ts' => '2026-09-03 14:40:11', 'user' => 'budi', 'event' => 'user melakukan 3x percobaan login gagal sebelum akhirnya berhasil'],
            ['id' => 3, 'ts' => '2026-09-05 21:02:47', 'user' => 'budi', 'event' => 'user mengekspor seluruh data akun (data export request)'],
        ],
        // --- Lab 8: log tanpa konteks investigasi ---
        'payment_log' => [
            '[2026-09-01 03:14:22] Payment processed: amount=Rp 250.000',
            '[2026-09-04 11:02:09] Payment processed: amount=Rp 1.200.000',
            '[2026-09-06 23:47:58] Payment processed: amount=Rp 50.000.000',
            '[2026-09-07 09:30:15] Payment processed: amount=Rp 375.000',
        ],
        'payment_log_v2' => [],
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
