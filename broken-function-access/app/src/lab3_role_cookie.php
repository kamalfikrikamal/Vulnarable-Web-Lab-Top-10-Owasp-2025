<?php
$title = 'Lab 3: Role ditentukan oleh cookie';
include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }

// VULNERABLE: trusts a client-writable cookie for the authorization
// decision instead of re-checking the authoritative role stored server-side
// against the session ($me['role']).
$effective_role = $_COOKIE['role'] ?? 'user';
$is_admin = ($effective_role === 'admin');
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: setelah login, server menyimpan role kamu di cookie <code>role</code>
supaya bisa dibaca cepat oleh halaman ini (harusnya cukup dicek dari data user di server, tapi
di lab ini sengaja dibuat salah). Cookie biasa (non-<code>HttpOnly</code>, tanpa signature) bisa
diubah bebas lewat DevTools &rarr; Application &rarr; Cookies, atau <code>document.cookie</code>
di console. Login sebagai <code>alice</code>, lalu ubah nilai cookie <code>role</code> menjadi
<code>admin</code> dan refresh halaman ini.</p>
<pre>document.cookie = "role=admin; path=/";</pre>
</details>

<p>Role dari data user (server, sebenarnya): <span class="badge <?php echo $me['role']==='admin'?'admin':'user'; ?>"><?php echo htmlspecialchars($me['role']); ?></span></p>
<p>Role dari cookie (dipakai untuk keputusan akses di halaman ini): <span class="badge <?php echo $is_admin?'admin':'user'; ?>"><?php echo htmlspecialchars($effective_role); ?></span></p>

<?php if ($is_admin): ?>
<div class="result-box"><?php echo htmlspecialchars($db['secret']); ?></div>
<?php if ($me['role'] !== 'admin'): ?>
<div class="error-box">Data user kamu sebenarnya bukan admin &mdash; ini terbuka murni karena cookie yang kamu ubah sendiri.</div>
<?php endif; ?>
<?php else: ?>
<div class="error-box">Akses ditolak. Kamu bukan admin (menurut cookie).</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
