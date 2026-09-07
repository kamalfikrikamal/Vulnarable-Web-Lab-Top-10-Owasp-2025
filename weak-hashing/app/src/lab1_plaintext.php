<?php
$title = 'Lab 1: Penyimpanan Password Plaintext';
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: bayangkan tabel di bawah ini adalah hasil dump database (mis. lewat SQL
Injection, backup yang bocor, atau insider threat) dari aplikasi "VulnShop". Karena password
disimpan apa adanya (bukan hash), begitu database bocor, <strong>seluruh</strong> password akun
langsung diketahui tanpa perlu cracking sama sekali.</p>
</details>

<h3>Dump tabel <code>users</code> (contoh kebocoran database)</h3>
<table class="data-table">
<tr><th>ID</th><th>Username</th><th>Password (kolom "password", VARCHAR biasa)</th></tr>
<?php foreach ($db['users_plaintext'] as $u): ?>
<tr><td><?php echo (int)$u['id']; ?></td><td><?php echo htmlspecialchars($u['username']); ?></td><td><code><?php echo htmlspecialchars($u['password']); ?></code></td></tr>
<?php endforeach; ?>
</table>

<div class="error-box">Semua password langsung terbaca dalam bentuk asli &mdash; tidak ada
proses cracking yang diperlukan. Kalau salah satu pengguna memakai ulang password ini di layanan
lain (hal yang sangat umum), akun mereka di layanan lain ikut terancam (credential stuffing).</div>

<?php include 'footer.php'; ?>
