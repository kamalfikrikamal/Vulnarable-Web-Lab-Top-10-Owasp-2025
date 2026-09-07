<?php
$title = 'Lab 5: Referer-based Access Control Bypass';
include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }

// VULNERABLE: "authorization" is decided by checking that the Referer
// header looks like it came from the admin menu page, instead of checking
// $me['role']. The Referer header is fully attacker-controlled - curl,
// Burp, or a crafted HTML page can send any value.
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$came_from_admin_menu = (strpos($referer, '/admin_menu.php') !== false);
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: halaman ini "menganggap" kamu berhak masuk kalau kamu datang dari menu
admin (<code>admin_menu.php</code>) &mdash; dicek lewat header <code>Referer</code>. Header ini
sepenuhnya dikontrol oleh pengirim request, jadi kirim saja permintaan dengan header
<code>Referer</code> palsu:</p>
<pre>curl -b "PHPSESSID=&lt;session alice&gt;" -H "Referer: http://target/admin_menu.php" http://target/lab5_referer_bypass.php</pre>
<p class="hint">Atau di browser: pasang extension pengubah header, atau replay request lewat
Burp Repeater sambil menambahkan header <code>Referer</code> itu secara manual.</p>
</details>

<p>Kamu login sebagai: <strong><?php echo htmlspecialchars($me['username']); ?></strong>
(role: <span class="badge <?php echo $me['role']==='admin'?'admin':'user'; ?>"><?php echo htmlspecialchars($me['role']); ?></span>)</p>
<p>Referer yang terdeteksi server: <code><?php echo htmlspecialchars($referer ?: '(kosong)'); ?></code></p>

<?php if ($came_from_admin_menu): ?>
<div class="result-box"><?php echo htmlspecialchars($db['secret']); ?></div>
<?php if ($me['role'] !== 'admin'): ?>
<div class="error-box">Kamu bukan admin, tapi Referer palsu sudah cukup untuk membuka halaman ini.</div>
<?php endif; ?>
<?php else: ?>
<div class="error-box">Akses ditolak &mdash; server mengharapkan Referer dari <code>admin_menu.php</code>.</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
