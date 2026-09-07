<?php
$title = 'Lab 1: Password Reset Token yang Bisa Diprediksi';
require_once __DIR__ . '/lib.php';
$db = load_db();
$msg = '';
$reveal_time = null;

function make_reset_token($username, $ts) {
    // VULNERABLE: token derived purely from public/guessable inputs
    // (username) and the server's current Unix timestamp - no
    // cryptographically secure random source (random_bytes()/random_int())
    // is involved at all.
    return substr(md5($username . $ts), 0, 16);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $username = $_POST['username'] ?? '';
    $ts = time();
    $token = make_reset_token($username, $ts);
    $db['reset_tokens'][] = ['username' => $username, 'token' => $token, 'created' => $ts, 'used' => false];
    save_db($db);
    $msg = "Jika akun \"" . htmlspecialchars($username) . "\" ada, link reset password telah \"dikirim\" ke email terdaftar.";
    $reveal_time = $ts; // shown here for lab purposes only - see hint box
}

$confirm_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    $username = $_POST['c_username'] ?? '';
    $token = $_POST['c_token'] ?? '';
    $found = null;
    foreach ($db['reset_tokens'] as &$t) {
        if ($t['username'] === $username && $t['token'] === $token && !$t['used'] && (time() - $t['created']) < 900) {
            $t['used'] = true;
            $found = $t;
            break;
        }
    }
    unset($t);
    save_db($db);
    $confirm_msg = $found ? 'Token valid! Password untuk "' . htmlspecialchars($username) . '" berhasil direset ke password baru pilihan attacker.' : 'Token tidak valid / kadaluarsa.';
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: token reset dibuat dari <code>substr(md5($username . time()), 0, 16)</code>.
Server tidak pernah menampilkan token ke UI (disimulasikan seperti "dikirim lewat email"), tapi
di dunia nyata attacker tetap tahu <strong>waktu server saat request dikirim</strong> lewat
header respons HTTP standar <code>Date</code> (selalu ada di setiap response, presisi 1 detik) —
lalu tinggal mencoba beberapa kandidat timestamp di sekitar waktu itu (untuk mengompensasi
latency jaringan/pemrosesan). Untuk lab ini, timestamp persis yang dipakai server ditampilkan
langsung di bawah supaya kamu bisa fokus ke perhitungan algoritmanya.</p>
</details>

<h3>1. Request Reset (sebagai attacker, untuk akun "admin")</h3>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="admin">
  <button type="submit" name="request_reset" value="1">Request Password Reset</button>
</form>
<?php if ($msg): ?>
<div class="ok-box"><?php echo $msg; ?></div>
<?php if ($reveal_time !== null): ?>
<p class="hint">[Untuk lab] Timestamp server saat token dibuat: <code><?php echo (int)$reveal_time; ?></code> (setara header <code>Date</code>)</p>
<?php endif; ?>
<?php endif; ?>

<h3>2. Token Calculator (hitung ulang token dari username + timestamp)</h3>
<form method="get" onsubmit="return false;">
  <label>Username</label><br>
  <input type="text" id="calc_user" value="admin"><br>
  <label>Timestamp</label><br>
  <input type="text" id="calc_ts" value="<?php echo (int)($reveal_time ?? time()); ?>"><br>
  <button onclick="document.getElementById('calc_out').innerText='Hitung manual di server (lihat form konfirmasi di bawah) - MD5 di JS tidak tersedia di sini, gunakan php -r atau CyberChef.'">Info</button>
</form>
<p class="hint">Hitung manual: <code>php -r "echo substr(md5('admin' . TIMESTAMP), 0, 16);"</code> (ganti TIMESTAMP dengan kandidat kamu), atau gunakan CyberChef (recipe: MD5).</p>

<h3>3. Confirm Reset (pakai token yang berhasil dihitung)</h3>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="c_username" value="admin"><br>
  <label>Token</label><br>
  <input type="text" name="c_token" placeholder="hasil perhitungan md5(...)">
  <button type="submit" name="confirm_reset" value="1">Reset Password</button>
</form>
<?php if ($confirm_msg): ?><div class="<?php echo strpos($confirm_msg,'valid!')!==false ? 'result-box' : 'error-box'; ?>"><?php echo htmlspecialchars($confirm_msg); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
