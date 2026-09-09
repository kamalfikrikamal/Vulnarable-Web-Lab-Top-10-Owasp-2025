<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'users' => [
            ['id' => 1, 'username' => 'alice', 'role' => 'user'],
            ['id' => 2, 'username' => 'admin', 'role' => 'admin'],
        ],
        'applied_updates' => [],
        // Lab 5: token recovery akun - md5('240610708'), sebuah "magic hash" PHP
        // yang bentuknya "0e" diikuti cuma digit, sehingga ditafsirkan sebagai
        // notasi ilmiah (0) oleh perbandingan == yang longgar.
        'magic_hash_token' => '0e462097431906509019562988736854',
        // Lab 8: riwayat "update" yang diterima lewat mekanisme checksum-dari-sumber-sama.
        'checksum_same_source_log' => [],
        // Lab 10: log pemanggilan function lewat dynamic dispatch.
        'dispatch_log' => [],
    ];
}

function load_db() {
    $path = db_path();
    if (!file_exists($path)) save_db(seed_db());
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : seed_db();
}

function save_db($db) { file_put_contents(db_path(), json_encode($db, JSON_PRETTY_PRINT)); }

function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
