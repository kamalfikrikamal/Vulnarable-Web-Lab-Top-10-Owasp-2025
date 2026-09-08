<?php
session_start();

// "User" yang sedang login disimulasikan tetap (tidak ada halaman login di lab ini -
// fokus lab ini adalah exception handling, bukan autentikasi).
define('CURRENT_USER', 'alice');

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'orders' => [],
        'next_order_id' => 9001,
        'reports' => [
            ['id' => 1, 'owner' => 'alice', 'title' => 'Laporan Pengeluaran Pribadi - Alice', 'content' => 'Rincian pengeluaran bulanan Alice: sewa Rp3.500.000, transportasi Rp800.000, lain-lain Rp1.200.000.'],
            ['id' => 2, 'owner' => 'admin', 'title' => 'Laporan Gaji Karyawan (RAHASIA)', 'content' => 'Data gaji seluruh karyawan: alice=Rp12.000.000, bob=Rp11.000.000, admin=Rp35.000.000. Dokumen ini hanya untuk HRD/Admin.'],
        ],
        'giftcard' => [
            'code' => 'GIFT100K',
            'balance' => 100000,
            'redeemed' => false,
        ],
        'wallet_balance' => 0,
        'redemption_log' => [],
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

function find_report($db, $owner) {
    foreach ($db['reports'] as $r) if ($r['owner'] === $owner) return $r;
    return null;
}

function find_report_by_id($db, $id) {
    foreach ($db['reports'] as $r) if ((string)$r['id'] === (string)$id) return $r;
    return null;
}
