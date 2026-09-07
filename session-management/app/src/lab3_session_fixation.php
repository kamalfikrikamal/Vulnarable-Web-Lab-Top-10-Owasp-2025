<?php
$title = 'Lab 3: Session Fixation';
require_once __DIR__ . '/lib.php';
$db = load_db();

// VULNERABLE: the app accepts a session token supplied by the URL (mis.
// "for convenience", e.g. cross-domain handoff) and adopts it as-is before
// the user has even logged in.
if (isset($_GET['fixed_token']) && empty($_COOKIE['authtoken3'])) {
    setcookie('authtoken3', $_GET['fixed_token'], 0, '/');
    header('Location: lab3_session_fixation.php?adopted=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user = find_user($db, $username);
    if ($user && $user['password'] === $password) {
        // VULNERABLE: reuses whatever token the browser already has
        // (possibly attacker-supplied via the URL above) instead of
        // regenerating a brand new one at the moment of privilege change.
        $token = $_COOKIE['authtoken3'] ?? bin2hex(random_bytes(16));
        $db['sessions'][$token] = $username;
        save_db($db);
        setcookie('authtoken3', $token, 0, '/');
        header('Location: lab3_session_fixation.php');
        exit;
    }
}

if (isset($_GET['logout'])) {
    setcookie('authtoken3', '', time() - 3600, '/');
    header('Location: lab3_session_fixation.php');
    exit;
}

$my_token = $_COOKIE['authtoken3'] ?? null;
$logged_in_as = $my_token ? username_for_token($db, $my_token) : null;

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
<p class="hint">Goal: sebagai attacker, kamu memilih sendiri nilai token, mis.
<code>FIXED-abc123</code>, lalu "mengirim link" itu ke korban:</p>
<pre>lab3_session_fixation.php?fixed_token=FIXED-abc123</pre>
<p class="hint">Buka link itu (mensimulasikan korban mengklik link dari attacker) &mdash; token
langsung ter-set di cookie SEBELUM korban login sama sekali. Lalu login di form di bawah sebagai
<code>alice/alice123</code> (mensimulasikan korban login). Karena server tidak pernah
mengganti token setelah login berhasil, token <code>FIXED-abc123</code> yang SUDAH kamu ketahui
dari awal kini terikat ke akun alice. Buka form "Cek akses dengan token" dengan
<code>FIXED-abc123</code> untuk membuktikan kamu bisa masuk sebagai alice.</p>
</details>

<p><a href="?fixed_token=FIXED-abc123">1. (Sebagai attacker) buat &amp; buka link dengan token pilihan sendiri</a></p>
<?php if (isset($_GET['adopted'])): ?><div class="ok-box">Token <code>FIXED-abc123</code> sudah diadopsi ke cookie kamu, sebelum login sama sekali.</div><?php endif; ?>

<h3>2. Login (mensimulasikan korban)</h3>
<?php if ($logged_in_as): ?>
<p>Login sebagai: <strong><?php echo htmlspecialchars($logged_in_as); ?></strong> (token: <code><?php echo htmlspecialchars($my_token); ?></code>)</p>
<a href="?logout=1">Logout</a>
<?php else: ?>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="alice"><br>
  <label>Password</label><br>
  <input type="text" name="password" value="alice123"><br>
  <button type="submit" name="login" value="1">Login</button>
</form>
<?php endif; ?>

<h3>3. Cek akses dengan token (sebagai attacker, pakai token yang sudah diketahui dari awal)</h3>
<form method="get">
  <label>Token</label><br>
  <input type="text" name="check_token" value="FIXED-abc123">
  <button type="submit">Cek Akses</button>
</form>
<?php if ($check_result): ?><div class="<?php echo strpos($check_result,'alice')!==false ? 'error-box' : 'ok-box'; ?>"><?php echo htmlspecialchars($check_result); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
