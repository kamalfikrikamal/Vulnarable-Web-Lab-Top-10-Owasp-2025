<?php
$title = 'Lab 3: Kode Reset Pendek Tanpa Rate Limiting';
require_once __DIR__ . '/lib.php';
$db = load_db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $username = $_POST['username'] ?? '';
    if (find_user($db, $username)) {
        $code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $db['reset_codes'][$username] = $code;
        save_db($db);
        $msg = 'Kode verifikasi 4 digit "dikirim ke SMS" ' . htmlspecialchars($username) . ' (tidak ditampilkan di sini - lihat brute-force tool di bawah).';
    }
}

$confirm_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    $username = $_POST['c_username'] ?? '';
    $code = $_POST['code'] ?? '';
    // VULNERABLE: no attempt counter, no lockout, no CAPTCHA on this step -
    // and the code space is tiny (4 digits = 10,000 possibilities).
    if (($db['reset_codes'][$username] ?? null) === $code) {
        $confirm_msg = 'Kode benar! Password untuk ' . htmlspecialchars($username) . ' berhasil direset.';
    } else {
        $confirm_msg = 'Kode salah.';
    }
}

$brute_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['brute_force'])) {
    $username = $_POST['b_username'] ?? '';
    $start = microtime(true);
    $found = null;
    $tries = 0;
    for ($i = 0; $i <= 9999; $i++) {
        $tries++;
        $candidate = str_pad((string)$i, 4, '0', STR_PAD_LEFT);
        if (($db['reset_codes'][$username] ?? null) === $candidate) { $found = $candidate; break; }
    }
    $elapsed = round(microtime(true) - $start, 3);
    $brute_result = $found ? "Kode ditemukan: $found (mencoba $tries kombinasi dalam {$elapsed}s)" : "Tidak ditemukan (habis $tries kombinasi).";
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: kode verifikasi cuma 4 digit (10.000 kemungkinan) dan form "Confirm
Reset" tidak membatasi jumlah percobaan sama sekali. Tool "Brute Force" di bawah mensimulasikan
serangan otomatis yang mencoba SEMUA 10.000 kombinasi secara berurutan &mdash; perhatikan betapa
cepatnya kode ditemukan.</p>
<p class="hint">Di dunia nyata: Burp Intruder dengan payload "Numbers" dari 0000-9999 (perlu
padding 4 digit) bisa menyelesaikan ini dalam hitungan detik-menit tergantung kecepatan
jaringan, karena tidak ada rate limiting yang memperlambatnya.</p>
</details>

<h3>1. Request Reset</h3>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="admin">
  <button type="submit" name="request_reset" value="1">Request Password Reset</button>
</form>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<h3>2. Brute Force Tool (coba semua kombinasi 0000-9999)</h3>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="b_username" value="admin">
  <button type="submit" name="brute_force" value="1">Brute Force Kode</button>
</form>
<?php if ($brute_result): ?><div class="result-box"><?php echo htmlspecialchars($brute_result); ?></div><?php endif; ?>

<h3>3. Confirm Reset (manual, dengan kode hasil brute force)</h3>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="c_username" value="admin"><br>
  <label>Kode</label><br>
  <input type="text" name="code" maxlength="4">
  <button type="submit" name="confirm_reset" value="1">Reset Password</button>
</form>
<?php if ($confirm_msg): ?><div class="<?php echo strpos($confirm_msg,'benar!')!==false ? 'result-box' : 'error-box'; ?>"><?php echo htmlspecialchars($confirm_msg); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
