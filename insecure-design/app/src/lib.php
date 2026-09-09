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
        // Lab 6/8: akun demo untuk alur login bertahap (password lalu OTP).
        'auth_account' => [
            'username' => 'alice',
            'password' => 'Password123',
            'otp' => '482913',
        ],
        // Lab 7: log perubahan password (tanpa pernah minta password lama).
        'password_change_log' => [],
        // Lab 9: kupon untuk demo HTTP Parameter Pollution (beda dari DISKON20 di Lab 3).
        'coupon_hpp' => ['code' => 'HEMAT10', 'percent' => 10],
        // Lab 10: harga produk digital yang seharusnya berbeda per region (server
        // seharusnya menentukan region dari sumber tepercaya, bukan header/cookie klien).
        'digital_product' => ['name' => 'Langganan Premium (1 tahun)', 'price_by_region' => ['ID' => 1500000, 'US' => 99]],
        // Lab 11: order yang sudah "dibeli" untuk didemokan pengajuan return-nya.
        'refund_orders' => [
            ['id' => 9001, 'product' => 'Sepatu Sneakers', 'price' => 450000, 'quantity' => 1, 'total_refunded_qty' => 0],
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
