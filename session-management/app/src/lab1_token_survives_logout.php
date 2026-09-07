<?php
$title = 'Lab 1: Token Tetap Valid Setelah Logout';
require_once __DIR__ . '/lib.php';
$db = load_db();

if (isset($_GET['login'])) {
    $token = bin2hex(random_bytes(16));
    $db['sessions'][$token] = 'alice';
    save_db($db);
    setcookie('authtoken1', $token, 0, '/');
    header('Location: lab1_token_survives_logout.php');
    exit;
}

if (isset($_GET['logout'])) {
    // VULNERABLE: only clears the cookie on the CLIENT. The token itself
    // is never removed from $db['sessions'], so it remains a fully valid
    // credential for anyone who captured it before logout.
    setcookie('authtoken1', '', time() - 3600, '/');
    header('Location: lab1_token_survives_logout.php');
    exit;
}

$my_token = $_COOKIE['authtoken1'] ?? null;
$check_token = $_GET['check_token'] ?? '';
$check_result = '';
if ($check_token !== '') {
    $u = username_for_token($db, $check_token);
    $check_result = $u ? "Token valid! Mengakses akun sebagai: $u" : 'Token tidak valid / tidak dikenal.';
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: login, catat nilai token yang muncul di bawah (mensimulasikan token yang
berhasil dicuri attacker, mis. lewat XSS atau sniffing jaringan tanpa TLS), lalu klik Logout.
Tempelkan token yang kamu catat tadi ke form "Cek akses dengan token" di bawah &mdash; token itu
akan tetap diterima meski kamu sudah logout.</p>
</details>

<?php if ($my_token && ($u = username_for_token($db, $my_token))): ?>
<p>Login sebagai: <strong><?php echo htmlspecialchars($u); ?></strong></p>
<p>Token kamu saat ini: <code><?php echo htmlspecialchars($my_token); ?></code> (catat ini sebelum logout!)</p>
<a href="?logout=1">Logout</a>
<?php else: ?>
<p>Belum login.</p>
<a href="?login=1">Login sebagai alice</a>
<?php endif; ?>

<h3>Cek akses dengan token (simulasi request dari attacker)</h3>
<form method="get">
  <label>Token</label><br>
  <input type="text" name="check_token" value="<?php echo htmlspecialchars($check_token); ?>">
  <button type="submit">Cek Akses</button>
</form>
<?php if ($check_result): ?><div class="<?php echo strpos($check_result,'valid!')!==false ? 'error-box' : 'ok-box'; ?>"><?php echo htmlspecialchars($check_result); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
