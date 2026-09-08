<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        'products' => [
            ['id' => 1, 'name' => 'Kaos Polos Import', 'price' => 150000],
            ['id' => 2, 'name' => 'Sepatu Sneakers', 'price' => 450000],
            ['id' => 3, 'name' => 'Tas Ransel Kanvas', 'price' => 275000],
        ],
        'orders' => [],
        'coupons' => [
            ['code' => 'DISKON20', 'percent' => 20, 'total_saved' => 0, 'times_used' => 0],
        ],
        'wallet_balance' => 0,
        'referral' => [
            'owner' => 'alice',
            'code' => 'ALICE-REF',
            'bonus_per_signup' => 50000,
            'wallet_balance' => 0,
            'signups' => [],
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

function next_order_id($db) {
    $max = 1000;
    foreach ($db['orders'] as $o) { if ($o['id'] > $max) $max = $o['id']; }
    return $max + 1;
}

function find_product($db, $id) {
    foreach ($db['products'] as $p) { if ((string)$p['id'] === (string)$id) return $p; }
    return null;
}

function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
