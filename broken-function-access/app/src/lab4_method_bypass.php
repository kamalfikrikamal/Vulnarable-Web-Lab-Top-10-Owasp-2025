<?php
$title = 'Lab 4: Method-based Access Control Bypass';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/lib.php';
    $db = load_db();
    $me = current_user($db);
    // VULNERABLE: the role check below only ever runs in the GET branch
    // further down this file. The POST handler that actually performs the
    // sensitive action never checks $me['role'] at all.
    $action_result = "Log audit sistem berhasil DIHAPUS oleh " . ($me['username'] ?? 'unknown') . " pada " . date('Y-m-d H:i:s') . ".";
}

include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: kalau kamu buka halaman ini lewat GET sebagai user biasa, tombol "Hapus
Log Audit" disembunyikan karena ada pengecekan role di render GET. Tapi pengecekan itu cuma ada
di jalur GET (untuk menampilkan/menyembunyikan tombol) &mdash; endpoint yang benar-benar
menjalankan aksi (menerima POST) tidak mengecek role sama sekali. Kirim POST langsung ke URL ini
tanpa perlu melihat tombolnya:</p>
<pre>curl -b "PHPSESSID=&lt;session alice&gt;" -X POST http://target/lab4_method_bypass.php</pre>
</details>

<p>Kamu login sebagai: <strong><?php echo htmlspecialchars($me['username']); ?></strong>
(role: <span class="badge <?php echo $me['role']==='admin'?'admin':'user'; ?>"><?php echo htmlspecialchars($me['role']); ?></span>)</p>

<?php if (isset($action_result)): ?>
<div class="result-box"><?php echo htmlspecialchars($action_result); ?></div>
<?php if ($me['role'] !== 'admin'): ?>
<div class="error-box">Aksi ini berhasil dijalankan walau kamu bukan admin &mdash; endpoint POST tidak pernah mengecek role.</div>
<?php endif; ?>
<?php endif; ?>

<?php if ($me['role'] === 'admin'): ?>
<form method="post">
  <button type="submit">Hapus Log Audit (admin only)</button>
</form>
<?php else: ?>
<p class="hint">(Tombol "Hapus Log Audit" disembunyikan di sini karena kamu bukan admin &mdash; tapi endpoint di baliknya tetap bisa dipanggil langsung, lihat hint di atas.)</p>
<?php endif; ?>

<?php include 'footer.php'; ?>
