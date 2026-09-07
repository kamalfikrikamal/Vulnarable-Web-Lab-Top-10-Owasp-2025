<?php
$title = 'Lab 3: "Enkripsi" yang Sebenarnya Cuma Encoding';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    // VULNERABLE: this is base64 ENCODING, not encryption - there is no key,
    // and anyone can reverse it with base64_decode(). The developer's
    // comment/variable name ("encrypted") is the whole bug: they believed
    // obfuscation was the same thing as cryptographic protection.
    $raw = $_POST['username'] . ':' . $_POST['password'];
    $encrypted_credentials = base64_encode($raw); // <- "encryption" (sic)
    setcookie('remember_me', $encrypted_credentials, time() + 3600, '/');
    header('Location: lab3_reversible_encoding.php');
    exit;
}

$cookie = $_COOKIE['remember_me'] ?? '';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: fitur "remember me" di bawah menyimpan kredensial ke cookie lewat fungsi
yang di kode sumbernya dinamai <code>$encrypted_credentials</code> &mdash; developer mengira ini
enkripsi. Padahal isinya cuma <code>base64_encode("username:password")</code>: tidak ada kunci
rahasia, tidak ada algoritma kriptografi apa pun, murni encoding yang bisa dibalik siapa saja.
Login di bawah, lalu lihat nilai cookie <code>remember_me</code> di DevTools &rarr; Application
&rarr; Cookies, dan decode base64-nya (browser console: <code>atob("...")</code>, atau situs
base64 decoder mana pun).</p>
</details>

<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="alice"><br>
  <label>Password</label><br>
  <input type="text" name="password" value="Summer2024!"><br>
  <button type="submit">Login dengan "Remember Me"</button>
</form>

<?php if ($cookie): ?>
<p>Nilai cookie <code>remember_me</code> saat ini: <code><?php echo htmlspecialchars($cookie); ?></code></p>
<div class="result-box">base64_decode(cookie) = <?php echo htmlspecialchars(base64_decode($cookie)); ?></div>
<div class="error-box">Kredensial lengkap (username:password) terbaca hanya dengan base64 decode &mdash; tidak ada kunci yang perlu ditebak sama sekali.</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
