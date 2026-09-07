<?php
$title = 'Lab 1: Tidak Ada Rate Limiting';
include 'header.php';

$default_wordlist = "123456\npassword\nadmin\nletmein\nqwerty123\nPassw0rd!\nSpring2024\nWelcome1\n";
$wordlist = $_POST['wordlist'] ?? $default_wordlist;
$results = [];
$tried = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidates = array_filter(array_map('trim', explode("\n", $wordlist)));
    // VULNERABLE: every candidate is tried back-to-back with zero delay,
    // zero CAPTCHA, and no lockout counter of any kind - a real attacker
    // would run this against a rockyou.txt-sized list in seconds.
    foreach ($candidates as $cand) {
        $tried++;
        if (check_login('admin', $cand)) {
            $results[] = "MATCH: admin / $cand";
        }
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: form login di bawah tidak menerapkan rate limiting, lockout, maupun
CAPTCHA apa pun. Tool "brute force" di bawah mensimulasikan serangan otomatis: masukkan
wordlist (satu password per baris), server akan mencoba semuanya secara berurutan tanpa
hambatan apa pun.</p>
<p class="hint">Di dunia nyata, ini dilakukan lewat Burp Intruder (sniper attack) atau script
sederhana yang mengirim request login berulang dengan password berbeda dari wordlist
(rockyou.txt, dsb).</p>
</details>

<h3>Login (manual)</h3>
<form method="post" style="margin-bottom:20px;">
  <input type="hidden" name="manual" value="1">
  <label>Username</label><br>
  <input type="text" name="username" value="admin"><br>
  <label>Password</label><br>
  <input type="text" name="password"><br>
  <button type="submit">Login</button>
</form>
<?php if (isset($_POST['manual'])): ?>
<div class="<?php echo check_login($_POST['username'] ?? '', $_POST['password'] ?? '') ? 'result-box' : 'error-box'; ?>">
<?php echo check_login($_POST['username'] ?? '', $_POST['password'] ?? '') ? 'Login berhasil!' : 'Username atau password salah.'; ?>
</div>
<?php endif; ?>

<h3>Brute-force tool (simulasi serangan otomatis)</h3>
<form method="post">
  <label>Wordlist (satu password per baris)</label><br>
  <textarea name="wordlist" rows="8" style="width:100%;"><?php echo htmlspecialchars($wordlist); ?></textarea><br>
  <button type="submit">Jalankan Brute Force ke admin</button>
</form>
<?php if ($tried > 0): ?>
<div class="<?php echo $results ? 'result-box' : 'error-box'; ?>">
Mencoba <?php echo $tried; ?> kandidat tanpa hambatan apa pun.
<?php echo $results ? "\n" . htmlspecialchars(implode("\n", $results)) : "\nTidak ada match di wordlist ini."; ?>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
