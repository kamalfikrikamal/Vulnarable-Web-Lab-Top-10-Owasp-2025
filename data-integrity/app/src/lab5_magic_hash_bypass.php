<?php
require_once __DIR__ . '/lib.php';
$title = 'Lab 5: Magic Hash / Type Juggling Bypass';
$db = load_db();

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recovery_code'])) {
    $submitted = (string)$_POST['recovery_code'];
    $submitted_hash = md5($submitted);
    $stored_hash = $db['magic_hash_token']; // '0e462097431906509019562988736854' = md5('240610708')

    // VULNERABLE: perbandingan pakai == (loose comparison), bukan ===/hash_equals().
    // PHP menafsirkan string yang berbentuk "0e" diikuti HANYA digit sebagai
    // notasi ilmiah (0 x 10^apa pun = 0) saat dibandingkan dengan ==, bukan
    // sebagai string biasa. Kalau KEDUA sisi kebetulan berbentuk seperti itu,
    // keduanya dianggap sama dengan 0 == 0, TANPA PERNAH benar-benar
    // membandingkan isi string aslinya.
    $match = ($submitted_hash == $stored_hash);

    $result = ['submitted' => $submitted, 'submitted_hash' => $submitted_hash, 'match' => $match];
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: lewati verifikasi kode recovery akun TANPA pernah tahu kode aslinya. Server
membandingkan <code>md5($kode_yang_kamu_kirim) == $hash_yang_tersimpan</code> — perhatikan ini
<code>==</code>, bukan <code>===</code>. Kirim string <code>QNKCDZO</code> sebagai kode recovery.</p>
</details>

<h3>Recovery Akun</h3>
<p>Masukkan kode recovery yang dikirim ke email terdaftar:</p>
<form method="post">
  <label>Kode recovery</label><br>
  <input type="text" name="recovery_code" placeholder="mis. QNKCDZO">
  <button type="submit">Verifikasi</button>
</form>

<h4>Hash tersimpan di server (untuk transparansi lab)</h4>
<div class="result-box"><?php echo htmlspecialchars($db['magic_hash_token']); ?></div>

<?php if ($result): ?>
<div class="<?php echo $result['match'] ? 'error-box' : 'ok-box'; ?>">
  Kode dikirim: <code><?php echo htmlspecialchars($result['submitted']); ?></code><br>
  <code>md5()</code>-nya: <code><?php echo htmlspecialchars($result['submitted_hash']); ?></code><br>
  Hasil: <strong><?php echo $result['match'] ? 'COCOK — akses recovery diberikan!' : 'Tidak cocok.'; ?></strong>
</div>
<?php endif; ?>

<p class="hint">Kenapa berhasil: <code>md5('QNKCDZO')</code> menghasilkan
<code>0e830400451993494058024219903391</code> — string yang SAMA SEKALI BEDA dari hash tersimpan
(<code>0e462097431906509019562988736854</code>, hasil dari <code>md5('240610708')</code>), tapi
KEDUANYA punya bentuk "0e" diikuti hanya digit. PHP <code>==</code> pada dua string numerik-mirip
seperti ini mengonversi keduanya jadi angka dulu sebelum membandingkan — dan
<code>0 x 10^830400... == 0 x 10^462097...</code> sama-sama bernilai <code>0</code>. Kode recovery
yang sesungguhnya tidak pernah ditebak/diketahui attacker sama sekali; yang dieksploitasi murni
adalah kelemahan tipe data pada operator perbandingan. Solusinya sederhana: pakai <code>===</code>
(strict, tidak pernah melakukan konversi tipe) atau <code>hash_equals()</code> untuk membandingkan
apa pun yang berfungsi sebagai secret/hash.</p>

<?php include 'footer.php'; ?>
