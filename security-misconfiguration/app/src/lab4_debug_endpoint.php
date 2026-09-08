<?php
$title = 'Lab 4: Debug Endpoint yang Terlupakan';
require_once __DIR__ . '/lib.php';
$db = load_db();

// Halaman ini adalah alat debug internal yang dipakai developer waktu
// development untuk mengecek konfigurasi environment - lalu terbawa
// ke production dan lupa dihapus/dilindungi. Tidak ada link ke halaman
// ini di navigasi mana pun, tapi URL-nya tetap bisa diakses siapa saja
// yang menebak atau menemukannya (mis. lewat wordlist/crawling).

// Simulasi kredensial/secret yang bocor lewat endpoint debug seperti ini.
$fake_env_vars = [
    'DB_PASSWORD' => 'Pr0d_DbP4ss_2024!',
    'AWS_SECRET_ACCESS_KEY' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'AWS_ACCESS_KEY_ID' => 'AKIAIOSFODNN7EXAMPLE',
    'JWT_SIGNING_KEY' => 'sup3r_s3cr3t_jwt_signing_key_do_not_leak',
    'MAIL_SMTP_PASSWORD' => 'Sm7p_M4il_2024!',
    'STRIPE_SECRET_KEY' => 'stripe-secret--DEMO-PLACEHOLDER--NOT-A-REAL-KEY--000000',
];

$fake_app_config = [
    'app_name' => 'ShopProd',
    'app_env' => 'production',
    'app_debug' => true,
    'timezone' => 'Asia/Jakarta',
    'session_driver' => 'file',
    'admin_email' => 'ops@corp.test',
];

include 'header.php';
?>

<p>Endpoint ini adalah tool debug internal yang dipakai tim development untuk mengecek
environment server saat itu — bukan halaman yang dimaksudkan untuk publik, dan seharusnya
dihapus atau dilindungi autentikasi sebelum deploy ke production. Karena tidak ada tautan ke
halaman ini dari mana pun, developer menganggapnya "aman" karena "tidak akan ditemukan" —
padahal endpoint yang tidak terhubung ke navigasi tetap bisa diakses langsung lewat URL-nya.</p>

<h3>PHP Info (ringkas)</h3>
<table class="data-table">
  <tr><th>PHP Version</th><td><?php echo htmlspecialchars(phpversion()); ?></td></tr>
  <tr><th>Document Root</th><td><?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? ''); ?></td></tr>
  <tr><th>Server Software</th><td><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? ''); ?></td></tr>
</table>

<h3>Environment Variables</h3>
<table class="data-table">
  <tr><th>Key</th><th>Value</th></tr>
  <?php foreach ($fake_env_vars as $k => $v): ?>
  <tr><td><?php echo htmlspecialchars($k); ?></td><td><?php echo htmlspecialchars($v); ?></td></tr>
  <?php endforeach; ?>
</table>

<h3>Application Config</h3>
<table class="data-table">
  <tr><th>Key</th><th>Value</th></tr>
  <?php foreach ($fake_app_config as $k => $v): ?>
  <tr><td><?php echo htmlspecialchars($k); ?></td><td><?php echo htmlspecialchars(is_bool($v) ? ($v ? 'true' : 'false') : (string)$v); ?></td></tr>
  <?php endforeach; ?>
</table>

<p class="hint">Perhatikan: kredensial cloud (<code>AWS_SECRET_ACCESS_KEY</code>), kunci
penandatanganan JWT, password database, dan secret key payment gateway semuanya terekspos di
satu halaman yang tidak dilindungi otentikasi apa pun.</p>

<?php include 'footer.php'; ?>
