<?php
$title = 'Lab 1: Debug Mode / Stack Trace Bocor';
require_once __DIR__ . '/lib.php';
$db = load_db();

// VULNERABLE: ini mensimulasikan environment variable APP_DEBUG=true yang
// tertinggal aktif di production. Banyak framework (Laravel, Symfony, dll)
// punya flag seperti ini - kalau lupa dimatikan saat deploy, halaman error
// akan menampilkan detail internal mentah-mentah (query, path, kredensial)
// langsung ke browser siapa pun yang memicu exception.
define('DEBUG_MODE', true);

$result = '';
$error_output = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $id = $_POST['product_id'];
    try {
        if (!is_numeric($id)) {
            // Simulasi query gagal karena input tidak valid - di aplikasi
            // nyata ini sering terjadi kalau ID dikirim ke query SQL/ORM
            // dan validasinya gagal di level yang lebih dalam.
            throw new Exception(
                "Query failed: SELECT * FROM products WHERE id = '$id' — " .
                "Connection: mysql:host=db-prod.internal;dbname=shop_prod;" .
                "user=shop_app;password=Pr0d_DbP4ss_2024! at /var/www/html/lib.php:42"
            );
        }
        $fake_products = [
            1 => 'Kabel USB-C 1m — Rp 45.000',
            2 => 'Mouse Wireless — Rp 120.000',
            3 => 'Keyboard Mekanik — Rp 650.000',
        ];
        $result = $fake_products[(int)$id] ?? 'Produk tidak ditemukan (ID valid, tapi tidak ada di katalog).';
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            // VULNERABLE: karena DEBUG_MODE true, pesan exception mentah
            // (termasuk connection string berisi password) dan trace
            // dicetak langsung ke halaman, bukan pesan generik.
            $error_output = $e->getMessage() . "\n\n" .
                "Stack trace:\n" .
                "#0 /var/www/html/lab1_debug_stacktrace.php(29): db_query('$id')\n" .
                "#1 /var/www/html/lab1_debug_stacktrace.php(45): search_product('$id')\n" .
                "#2 {main}\n" .
                "  thrown in /var/www/html/lib.php on line 42";
        } else {
            $error_output = 'Terjadi kesalahan. Silakan coba lagi nanti.';
        }
    }
}

include 'header.php';
?>

<p>Form pencarian produk berdasarkan ID di bawah ini terhubung ke "database" backend. Aplikasi
ini di-deploy dengan mode debug (<code>APP_DEBUG=true</code>) masih aktif — sesuatu yang lumrah
dibiarkan menyala di environment development, tapi berbahaya kalau lupa dimatikan saat masuk
production.</p>

<h3>Cari Produk berdasarkan ID</h3>
<form method="post">
  <label>Product ID</label><br>
  <input type="text" name="product_id" value="<?php echo htmlspecialchars($_POST['product_id'] ?? '1'); ?>">
  <button type="submit">Cari</button>
</form>

<?php if ($result): ?>
<div class="ok-box"><?php echo htmlspecialchars($result); ?></div>
<?php endif; ?>

<?php if ($error_output): ?>
<div class="error-box"><pre><?php echo htmlspecialchars($error_output); ?></pre></div>
<p class="hint">Perhatikan pesan error di atas: connection string database bocor lengkap dengan
<code>user=shop_app;password=Pr0d_DbP4ss_2024!</code> — kredensial database production yang
seharusnya tidak pernah terlihat oleh siapa pun di luar tim backend. Ini terjadi murni karena
<code>DEBUG_MODE</code> dibiarkan aktif; pesan error generik seharusnya ditampilkan ke user,
sementara detail lengkap hanya masuk ke log server.</p>
<?php endif; ?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Coba masukkan input yang bukan angka, misalnya <code>abc</code> atau
<code>1' OR '1'='1</code>, ke kolom Product ID. Input numerik valid (mis. <code>1</code>,
<code>2</code>, <code>3</code>) akan lolos validasi dan hanya menampilkan hasil produk biasa.</p>
</details>

<?php include 'footer.php'; ?>
