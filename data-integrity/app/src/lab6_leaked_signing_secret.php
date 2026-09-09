<?php
require_once __DIR__ . '/lib.php';
$title = 'Lab 6: Signing Secret Bocor di Client-Side JS';

// VULNERABLE: secret yang dipakai menandatangani link reset password server-side
// adalah PERSIS SAMA dengan yang ditaruh di reset_preview.js (dimuat publik,
// tanpa autentikasi, oleh siapa pun yang membuka halaman ini) - developer
// menambahkannya di sana untuk fitur "live preview" tanpa sadar itu artinya
// mempublikasikan secret yang seharusnya rahasia sepenuhnya.
define('RESET_LINK_SECRET', 'corp-reset-2024-preview-key');

function sign_reset_link($email) {
    return hash_hmac('sha256', $email, RESET_LINK_SECRET);
}

$verify_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['token'])) {
    $email = (string)$_POST['email'];
    $token = (string)$_POST['token'];
    $expected = sign_reset_link($email);
    $verify_result = ['email' => $email, 'token' => $token, 'valid' => hash_equals($expected, $token)];
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: buat link reset password yang VALID untuk email
<code>admin@corp.test</code> TANPA pernah memintanya lewat form "Lupa Password" yang sah (yang
seharusnya mengirim link itu lewat inbox email admin, bukan ke kamu). Halaman ini memuat
<a href="reset_preview.js" target="_blank"><code>reset_preview.js</code></a> untuk fitur "live
preview" — buka file itu langsung dan baca isinya.</p>
</details>

<h3>Buat &amp; Verifikasi Token Reset Password</h3>
<p>Simulasikan proses generate token untuk membuktikan pemahamanmu — di dunia nyata, endpoint
"Lupa Password" yang sah HANYA mengirim token ini lewat email terdaftar, tidak pernah
menampilkannya di halaman.</p>
<form method="post">
  <label>Email target</label><br>
  <input type="text" name="email" value="admin@corp.test"><br>
  <label>Token (hasil HMAC-SHA256 yang kamu hitung sendiri)</label><br>
  <input type="text" name="token" placeholder="tempel hasil perhitunganmu di sini">
  <button type="submit">Verifikasi Token</button>
</form>

<?php if ($verify_result): ?>
<div class="<?php echo $verify_result['valid'] ? 'error-box' : 'ok-box'; ?>">
  Email: <code><?php echo htmlspecialchars($verify_result['email']); ?></code><br>
  Token dikirim: <code><?php echo htmlspecialchars($verify_result['token']); ?></code><br>
  Hasil: <strong><?php echo $verify_result['valid'] ? 'VALID — link reset ini akan diterima server!' : 'Tidak valid.'; ?></strong>
</div>
<?php endif; ?>

<details class="hint-box">
<summary>Cara menghitung token sendiri (kalau butuh)</summary>
<pre class="result-box">php -r "echo hash_hmac('sha256', 'admin@corp.test', 'corp-reset-2024-preview-key');"</pre>
</details>

<p class="hint">Kenapa berhasil: mekanisme signing di sini secara teknis sudah benar (HMAC-SHA256
asli, dibandingkan dengan <code>hash_equals()</code> yang constant-time — beda dari Lab 3) tapi
seluruh keamanannya runtuh karena SECRET yang dipakai menandatangani bukan rahasia sama sekali —
ada di file JavaScript publik yang dimuat semua orang yang membuka halaman ini, tanpa autentikasi
apa pun. Siapa pun yang membaca <code>reset_preview.js</code> bisa menghitung token valid untuk
EMAIL SIAPA PUN, termasuk admin, tanpa pernah menerima email reset yang sesungguhnya. Integritas
sebuah signature cuma sekuat kerahasiaan kunci yang dipakai membuatnya — algoritma yang benar
tidak ada artinya kalau kuncinya bocor.</p>

<?php include 'footer.php'; ?>
