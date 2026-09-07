<?php
$title = 'Lab 2: Response Nyaris Identik';
include 'header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user = find_user($db, $username);
    // VULNERABLE: both messages LOOK identical when rendered by a browser,
    // but they are different strings at the byte level (one has a trailing
    // period, the other doesn't) - visible in page source, curl, or a
    // byte-for-byte diff tool like Burp Comparer.
    if (!$user) {
        $error = 'Invalid username or password.';
    } elseif ($user['password'] !== $password) {
        $error = 'Invalid username or password';
    } else {
        $error = '';
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: pesan error di sini terlihat SAMA PERSIS di layar untuk username salah vs
password salah. Tapi keduanya adalah string yang berbeda satu karakter (titik di akhir kalimat).
Bandingkan lewat "View Page Source" (Ctrl+U) untuk dua percobaan berbeda, atau lebih presisi
lewat <code>curl -s ... | xxd | tail</code> / Burp Comparer untuk melihat perbedaan byte demi
byte.</p>
<ul class="hint">
<li>Coba: <code>randomuser123</code> / password apa saja &rarr; perhatikan akhir kalimat error.</li>
<li>Coba: <code>alice</code> / password salah &rarr; bandingkan dengan yang di atas.</li>
</ul>
</details>

<form method="post">
  <label>Username</label><br>
  <input type="text" name="username"><br>
  <label>Password</label><br>
  <input type="password" name="password"><br>
  <button type="submit">Login</button>
</form>
<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
