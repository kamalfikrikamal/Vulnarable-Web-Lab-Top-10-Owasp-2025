<?php
$title = 'Lab 4: Data Sensitif Ikut Tercatat di Log';
include 'header.php';

// Seed beberapa entri "user lain" yang sudah pernah submit form sebelum kamu
// datang, supaya ada yang langsung ditemukan tanpa perlu menunggu korban lain.
$seed_file = log_path('debug_requests.log');
if (!file_exists($seed_file)) {
    $seed = "[2026-08-30 09:14:02] POST /update-kartu \xe2\x80\x94 card_number=4532111122223333, cvv=812, name=Siti Rahma\n"
          . "[2026-08-31 16:47:55] POST /update-kartu \xe2\x80\x94 card_number=5500001111220099, cvv=044, name=Budi Santoso\n"
          . "[2026-09-02 11:03:18] POST /update-kartu \xe2\x80\x94 card_number=4024007198765432, cvv=901, name=Dewi Anggraini\n";
    file_put_contents($seed_file, $seed);
}

$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_number'])) {
    $submitted = true;
    $card = $_POST['card_number'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    $name = $_POST['name'] ?? '';

    // VULNERABLE: "buat debugging", seluruh data form termasuk nomor kartu dan
    // CVV mentah ditulis apa adanya ke log dalam bentuk plaintext.
    $line = '[' . date('Y-m-d H:i:s') . '] POST /update-kartu — card_number=' . $card . ', cvv=' . $cvv . ', name=' . $name;
    append_log('debug_requests.log', $line);
}

$view = $_GET['view'] ?? '';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: form "Update Kartu Kredit" di bawah menulis SELURUH data form &mdash;
termasuk nomor kartu penuh dan CVV &mdash; ke <code>data/debug_requests.log</code> dalam bentuk
plaintext, "supaya gampang di-debug kalau ada error". Log ini juga sudah berisi beberapa entri
dari user lain yang sudah pernah submit form sebelum kamu.</p>
<p class="hint">Submit form dengan data kartu (bebas, cuma demo) lalu buka
<a href="?view=raw_log">?view=raw_log</a> &mdash; endpoint debug internal yang "lupa" dilepas dari
produksi &mdash; untuk melihat log mentahnya, termasuk kartu milikmu <em>dan</em> milik semua
user lain.</p>
</details>

<?php if ($view !== 'raw_log'): ?>

<h3>Update Kartu Kredit</h3>
<form method="post">
  <label>Nama di Kartu</label><br>
  <input type="text" name="name" value=""><br>
  <label>Nomor Kartu</label><br>
  <input type="text" name="card_number" value="" placeholder="4111111111111111"><br>
  <label>CVV</label><br>
  <input type="text" name="cvv" value="" maxlength="4"><br>
  <button type="submit">Simpan Kartu</button>
</form>

<?php if ($submitted): ?>
<div class="ok-box">Kartu berhasil disimpan (demo). Data form kamu barusan otomatis ikut ditulis
ke log debug internal &mdash; buka <a href="?view=raw_log">?view=raw_log</a> untuk melihatnya.</div>
<?php endif; ?>

<?php else: ?>

<h3>/update-kartu?view=raw_log &mdash; Debug Endpoint (internal, "harusnya" tidak publik)</h3>
<p class="hint">Endpoint ini dibuat developer untuk debugging cepat lalu lupa dihapus/dibatasi
aksesnya. Siapa pun yang tahu URL-nya bisa membaca seluruh riwayat request mentah &mdash;
termasuk data kartu kredit dan CVV semua user yang pernah submit form ini.</p>
<div class="result-box"><?php echo htmlspecialchars(read_log('debug_requests.log')); ?></div>
<p><a href="lab4_sensitive_data_in_logs.php">&larr; Kembali ke form</a></p>

<?php endif; ?>

<?php include 'footer.php'; ?>
