<?php
$title = 'Lab 2: Token Reset Bisa Dipakai Berulang Kali';
require_once __DIR__ . '/lib.php';
$db = load_db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $username = $_POST['username'] ?? '';
    if (find_user($db, $username)) {
        $token = bin2hex(random_bytes(8));
        // Note: no 'used' flag tracked here at all in this lab's store.
        $db['reset_tokens'][$token] = ['username' => $username];
        save_db($db);
        $msg = 'Token reset untuk ' . htmlspecialchars($username) . ': <code>' . htmlspecialchars($token) . '</code> (ditampilkan di sini untuk kebutuhan lab, biasanya lewat email).';
    }
}

$confirm_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    // VULNERABLE: validity is only checked by "does this token exist" -
    // it is never invalidated/deleted/marked-used after a successful reset,
    // so the exact same token can be replayed to reset the password again
    // and again, indefinitely.
    if (!empty($db['reset_tokens'][$token])) {
        $confirm_msg = 'Password untuk ' . htmlspecialchars($db['reset_tokens'][$token]['username']) . ' berhasil direset menjadi "' . htmlspecialchars($new_password) . '". Token TIDAK dihapus/di-invalidate.';
    } else {
        $confirm_msg = 'Token tidak valid.';
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: request reset untuk <code>admin</code>, catat tokennya, lalu gunakan token
yang sama itu berkali-kali di form "Confirm Reset" di bawah &mdash; setiap kali akan tetap
berhasil "mereset" password (ke nilai apa pun yang kamu masukkan), karena token tidak pernah
ditandai sudah dipakai.</p>
<p class="hint">Dampak nyata: kalau token ini pernah bocor sekali saja (mis. lewat log, riwayat
browser, atau kelalaian lain), attacker bisa mereset password akun itu kapan pun mereka mau,
tanpa batas waktu maupun jumlah pemakaian.</p>
</details>

<h3>1. Request Reset</h3>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="admin">
  <button type="submit" name="request_reset" value="1">Request Password Reset</button>
</form>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<h3>2. Confirm Reset (coba beberapa kali dengan token yang sama)</h3>
<form method="post">
  <label>Token</label><br>
  <input type="text" name="token">
  <label>Password baru</label><br>
  <input type="text" name="new_password" value="hacked123">
  <button type="submit" name="confirm_reset" value="1">Reset Password</button>
</form>
<?php if ($confirm_msg): ?><div class="<?php echo strpos($confirm_msg,'berhasil')!==false ? 'result-box' : 'error-box'; ?>"><?php echo $confirm_msg; ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
