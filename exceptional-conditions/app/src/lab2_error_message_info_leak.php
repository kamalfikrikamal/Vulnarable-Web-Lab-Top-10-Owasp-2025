<?php
declare(strict_types=1);

// Simulasi server produksi yang lupa mematikan display_errors (seharusnya OFF di prod,
// tapi banyak deployment nyata lupa mengubahnya dari default php.ini development).
ini_set('display_errors', '1');
error_reporting(E_ALL);

$title = 'Lab 2: Error Message Info Leak';
include 'header.php';

// Fungsi bertipe ketat (strict_types=1 di atas berlaku untuk seluruh file ini) - dipakai
// di bagian "verifikasi ulang" di bawah. TIDAK ADA validasi/cast tipe sebelum data form
// (yang SELALU berupa string) dilempar ke fungsi ini.
function calc(int $harga, int $jumlah): float {
    return $harga / $jumlah;
}

// Tabel diskon HANYA didefinisikan untuk jumlah_item 1 sampai 5. Developer berasumsi
// user tidak akan pernah mengisi angka di luar rentang itu.
$discount_tiers = [
    1 => 0,
    2 => 5,
    3 => 10,
    4 => 15,
    5 => 20,
];

$harga_input = '';
$jumlah_input = '';
$result = null;
$diskon = null;

$harga_strict_input = '';
$strict_result = null;
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Form "Cek Diskon" di bawah TIDAK punya penanganan error/validasi input sama
sekali - kode-nya menganggap input selalu berupa angka wajar. Coba: (1) <code>jumlah_item =
0</code> untuk memicu <em>division by zero</em>, (2) <code>jumlah_item = 999</code> atau angka
negatif untuk memicu <em>undefined array key</em> (tabel diskon cuma didefinisikan untuk 1-5),
(3) <code>harga = abc</code> (bukan angka) untuk memicu <code>TypeError</code>. Semua pesan error
bawaan PHP ini tampil apa adanya di halaman - lihat baik-baik, apakah ada informasi yang
seharusnya tidak pernah dilihat pengguna biasa (path folder di server, nama file, nomor baris
kode)?</p>
</details>

<div class="lab-card">
  <h3>Cek Diskon</h3>
  <form method="post">
    <label>Harga (Rp)</label><br>
    <input type="text" name="harga" value="<?php echo htmlspecialchars($harga_input); ?>"><br>
    <label>Jumlah Item</label><br>
    <input type="text" name="jumlah_item" value="<?php echo htmlspecialchars($jumlah_input); ?>"><br>
    <button type="submit">Hitung</button>
  </form>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['harga']) && !isset($_POST['harga_strict'])) {
    $harga_input = $_POST['harga'];
    $jumlah_input = $_POST['jumlah_item'];

    // BUG: tidak ada is_numeric()/validasi apa pun. Input dipakai langsung apa adanya.
    $result = $harga_input / $jumlah_input;          // (a) DivisionByZeroError kalau jumlah_item = "0"
    $diskon = $discount_tiers[$jumlah_input];         // (b) Undefined array key kalau di luar 1-5
    ?>
    <div class="ok-box">
      Rata-rata harga per item: Rp<?php echo number_format((float)$result, 0, ',', '.'); ?>
      &mdash; Diskon tier: <?php echo htmlspecialchars((string)$diskon); ?>%
    </div>
    <?php
}
?>

<div class="lab-card">
  <h3>Verifikasi Ulang (fungsi <code>calc()</code>, mode strict)</h3>
  <p class="hint">Field ini memanggil fungsi <code>calc(int $harga, int $jumlah): float</code>
  secara langsung dengan data form APA ADANYA (tanpa validasi/cast). Karena data dari
  <code>$_POST</code> selalu berupa string dan file ini pakai <code>declare(strict_types=1)</code>,
  PHP tidak akan pernah mengonversi otomatis string ke int di sini - coba masukkan
  <code>abc</code> untuk melihat <code>TypeError</code> penuh (bahkan angka biasa pun akan
  gagal di endpoint ini karena tidak ada cast sama sekali - itulah intinya: menambahkan
  strict typing tanpa validasi input di baliknya justru membuat SETIAP request meledak).</p>
  <form method="post">
    <label>Harga (Rp)</label><br>
    <input type="text" name="harga_strict" value="<?php echo htmlspecialchars($harga_strict_input); ?>"><br>
    <button type="submit">Verifikasi</button>
  </form>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['harga_strict'])) {
    $harga_strict_input = $_POST['harga_strict'];
    // BUG: dilempar mentah-mentah ke parameter int strict, tanpa (int) cast atau is_numeric().
    $strict_result = calc($harga_strict_input, 1);
    ?>
    <div class="ok-box">Hasil verifikasi: <?php echo htmlspecialchars((string)$strict_result); ?></div>
    <?php
}

include 'footer.php';
