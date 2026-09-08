<?php
$title = 'Lab 1: Log Injection / Log Forgery';
include 'header.php';

$SEED_PASSWORD = 'demo123'; // demo doang, fokus lab ini BUKAN kekuatan password

$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;
    $username_raw = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $result = ($password === $SEED_PASSWORD) ? 'SUCCESS' : 'FAILED';

    // VULNERABLE: username mentah (bisa mengandung \n) langsung ditulis ke log,
    // tanpa strip/encode karakter kontrol apa pun. Siapa pun yang menyisipkan
    // newline di username bisa "menyuntikkan" baris log baru yang terlihat sah.
    $line = '[' . date('Y-m-d H:i:s') . '] LOGIN ATTEMPT: user=' . $username_raw . ', result=' . $result;
    append_log('auth.log', $line);
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: form login di bawah menulis setiap percobaan ke <code>data/auth.log</code>
memakai <strong>username mentah</strong> dari input kamu, tanpa membuang karakter newline
(<code>\n</code>). Karena field username di sini berupa <code>&lt;textarea&gt;</code>, kamu bisa
benar-benar mengetik/menempel baris baru di dalamnya.</p>
<p class="hint">Coba isi username dengan sesuatu seperti:</p>
<pre>bob
[2026-01-01 03:00:00] LOGIN ATTEMPT: user=admin, result=SUCCESS</pre>
<p class="hint">Password bebas isi apa saja (login akan gagal, tidak masalah &mdash; yang penting
lihat apa yang tertulis di <code>auth.log</code> di bawah setelah submit).</p>
</details>

<h3>Login</h3>
<form method="post">
  <label>Username (textarea &mdash; boleh multi-baris)</label><br>
  <textarea name="username" rows="3" style="width:100%;"><?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?></textarea><br>
  <label>Password</label><br>
  <input type="text" name="password" value=""><br>
  <button type="submit">Login</button>
</form>

<?php if ($submitted): ?>
<div class="<?php echo $result === 'SUCCESS' ? 'ok-box' : 'error-box'; ?>">
Hasil login: <strong><?php echo $result; ?></strong> &mdash; sekarang lihat baris apa saja yang
baru ditambahkan ke <code>auth.log</code> di bawah.
</div>
<?php endif; ?>

<h3>Lihat auth.log (SOC / Investigator View)</h3>
<p class="hint">Bagian ini mensimulasikan tim SOC/investigator yang membuka file log mentah untuk
menyelidiki insiden. Log ditampilkan apa adanya sebagai teks biasa (jadi lab ini bukan tentang
XSS di viewer-nya &mdash; fokusnya murni pada baris log yang bisa dipalsukan).</p>
<div class="result-box"><?php echo htmlspecialchars(read_log('auth.log')) ?: '(auth.log masih kosong, coba login dulu)'; ?></div>

<?php if ($submitted && strpos($username_raw, "\n") !== false): ?>
<div class="error-box">
Perhatikan: baris <code>user=admin, result=SUCCESS</code> di atas <strong>bukan</strong> login
admin yang sungguhan &mdash; itu baris palsu yang berhasil kamu suntikkan lewat newline di field
username. Kalau ini log produksi sungguhan, tim investigator bisa saja percaya admin benar-benar
login sukses jam segitu &mdash; menyesatkan investigasi insiden, bahkan bisa dipakai membangun
alibi palsu atau menuduh orang lain melakukan sesuatu yang tidak pernah terjadi.
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
