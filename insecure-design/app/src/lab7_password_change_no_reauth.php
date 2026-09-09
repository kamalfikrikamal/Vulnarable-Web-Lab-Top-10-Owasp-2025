<?php
$title = 'Lab 7: Ganti Password Tanpa Re-autentikasi';
include 'header.php';

// Simulasi "sedang login" - tidak perlu form login sungguhan di sini, lab ini
// fokus murni ke langkah ganti password itu sendiri.
if (empty($_SESSION['la7_logged_in'])) $_SESSION['la7_logged_in'] = true;

$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABLE: form ganti password TIDAK meminta password LAMA, dan tidak
    // ada pengecekan "kapan terakhir kali user ini benar-benar login" (step-up
    // auth) - siapa pun yang kebetulan sedang membawa session ini (dicuri,
    // dipinjam, tertinggal di komputer publik) bisa ganti password akun tanpa
    // pernah tahu password aslinya sama sekali.
    $new_password = (string)($_POST['new_password'] ?? '');
    if ($new_password !== '') {
        $db['auth_account']['password'] = $new_password;
        $db['password_change_log'][] = [
            'new_password' => $new_password,
            'old_password_provided' => false,
            'time' => date('Y-m-d H:i:s'),
        ];
        save_db($db);
        $db = load_db();
        $msg = 'Password berhasil diganti.';
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: perhatikan form di bawah — tidak ada field "password lama" sama sekali.
Ganti password akun cuma dengan mengisi password baru, lalu lihat log perubahan password di
bawah untuk membuktikan tidak pernah ada verifikasi password lama yang terjadi.</p>
</details>

<?php if ($msg): ?><div class="ok-box"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<h3>Ganti Password</h3>
<p>Kamu "sedang login" sebagai <code>alice</code> (sesi aktif — anggap saja sesi ini didapat
lewat cara apa pun: login normal, cookie yang dicuri, atau komputer publik yang lupa di-logout).</p>
<form method="post">
  <label>Password baru</label><br>
  <input type="password" name="new_password" placeholder="Password baru bebas apa saja">
  <button type="submit">Ganti Password</button>
</form>

<?php if (!empty($db['password_change_log'])): ?>
<h3>Log Perubahan Password</h3>
<table class="data-table">
<tr><th>Waktu</th><th>Password lama diminta?</th><th>Password baru</th></tr>
<?php foreach (array_reverse($db['password_change_log']) as $entry): ?>
<tr>
  <td><?php echo htmlspecialchars($entry['time']); ?></td>
  <td><?php echo $entry['old_password_provided'] ? 'Ya' : '<strong>TIDAK PERNAH</strong>'; ?></td>
  <td><code><?php echo htmlspecialchars($entry['new_password']); ?></code></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<p class="hint">Kenapa berhasil: mengganti password adalah aksi sensitif yang seharusnya menuntut
<em>step-up authentication</em> — bukti bahwa orang yang mengklik "Ganti Password" masih benar-benar
pemilik akun, biasanya lewat "masukkan password saat ini" atau re-verifikasi 2FA. Desain form ini
mengasumsikan "kalau sesi masih aktif, pasti pemiliknya yang sedang memakai" — asumsi yang keliru
kalau sesi bisa dicuri (session hijacking), dibajak sebentar (komputer publik, perangkat dipinjam
teman), atau bertahan lebih lama dari yang disadari user. Tanpa re-autentikasi, siapa pun yang
kebetulan "menumpang" sesi aktif bisa mengunci pemilik asli keluar dari akunnya sendiri secara
permanen, tanpa pernah perlu tahu password aslinya.</p>

<?php include 'footer.php'; ?>
