<?php
$title = 'Lab 1: Unprotected Admin Functionality';
include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }

// VULNERABLE: only checks that *someone* is logged in ($me !== null).
// Never checks $me['role'] === 'admin' before showing admin-only content.
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman ini seharusnya cuma untuk admin, tapi satu-satunya pengecekan yang
dilakukan server adalah "apakah user sudah login" &mdash; tidak ada pengecekan role sama sekali.
Login sebagai <code>alice</code> (bukan admin) lalu buka langsung halaman ini.</p>
</details>

<p>Kamu login sebagai: <strong><?php echo htmlspecialchars($me['username']); ?></strong>
(role: <span class="badge <?php echo $me['role']==='admin'?'admin':'user'; ?>"><?php echo htmlspecialchars($me['role']); ?></span>)</p>

<div class="result-box"><?php echo htmlspecialchars($db['secret']); ?></div>

<?php if ($me['role'] !== 'admin'): ?>
<div class="error-box">Kamu bukan admin, tapi tetap bisa melihat config sistem ini &mdash; bukti Broken Function-Level Access Control.</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
