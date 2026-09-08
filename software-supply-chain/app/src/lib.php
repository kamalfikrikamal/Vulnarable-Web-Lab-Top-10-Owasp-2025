<?php
session_start();

function db_path() { return __DIR__ . '/data/db.json'; }

function seed_db() {
    return [
        // Lab 2: paket-paket yang berhasil "dipublikasikan" ke public registry (nama -> isi kode).
        'public_registry' => [],
        // Lab 5: paket pihak ketiga yang disubmit lengkap dengan skrip lifecycle (postinstall).
        'submitted_packages' => [],
    ];
}

function load_db() {
    $path = db_path();
    if (!file_exists($path)) save_db(seed_db());
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : seed_db();
}

function save_db($db) { file_put_contents(db_path(), json_encode($db, JSON_PRETTY_PRINT)); }

// Lab 4: menyiapkan dua "paket update" contoh di data/ - satu yang legit, satu yang sudah
// dimodifikasi/ditampering - supaya lab bisa dicoba tanpa akses internet sama sekali.
function ensure_update_files() {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);

    $official = $dir . '/official_update.txt';
    if (!file_exists($official)) {
        file_put_contents($official, "AcmePlugin Auto-Updater\r\nVersion: 2.4.1\r\nPublisher: Acme Corp (official)\r\nRelease notes: perbaikan bug minor dan peningkatan performa cache.\r\n");
    }

    $tampered = $dir . '/tampered_update.txt';
    if (!file_exists($tampered)) {
        file_put_contents($tampered, "AcmePlugin Auto-Updater\r\nVersion: 2.4.1\r\nPublisher: Acme Corp (official)\r\nRelease notes: perbaikan bug minor dan peningkatan performa cache.\r\n[!!] MALICIOUS CODE INJECTED: backdoor.php ditanam di direktori plugin, membuka reverse shell ke attacker.example:4444 setiap kali plugin dimuat.\r\n");
    }
}
