<?php
$title = 'Lab 3: Lockout Bypass lewat Variasi Kapitalisasi Username';
require_once __DIR__ . '/lib.php';
$db = load_db();

function login_case_insensitive($username, $password) {
    // Authentication itself is case-INSENSITIVE on the username (a common,
    // user-friendly design choice: "Admin" and "admin" are the same account).
    $db = load_db();
    foreach ($db['users'] as $u) {
        if (strcasecmp($u['username'], $username) === 0 && $u['password'] === $password) return true;
    }
    return false;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    // VULNERABLE: but the failed-attempt counter is keyed by the EXACT,
    // case-sensitive string the client submitted - "admin", "Admin", and
    // "ADMIN" each get their own independent counter, even though they all
    // authenticate against the very same account.
    $key = $username;
    $count = $db['attempts_by_username'][$key] ?? 0;
    if ($count >= 3) {
        $msg = "Username \"$key\" (persis seperti dikirim) dikunci sementara.";
    } else {
        if (login_case_insensitive($username, $password)) {
            $db['attempts_by_username'][$key] = 0;
            save_db($db);
            $msg = 'Login berhasil!';
        } else {
            $db['attempts_by_username'][$key] = $count + 1;
            save_db($db);
            $msg = "Login gagal untuk \"$key\" (percobaan ke-" . ($count + 1) . "/3 untuk varian penulisan ini).";
        }
    }
}
include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: login sendiri case-insensitive (<code>admin</code> = <code>Admin</code> =
<code>ADMIN</code>, akun yang sama). Tapi counter percobaan gagal disimpan berdasarkan string
<strong>persis</strong> yang dikirim client, bukan bentuk yang sudah dinormalisasi
(<code>strtolower()</code>). Ganti kapitalisasi username di tiap beberapa percobaan untuk
mendapat "jatah" 3x percobaan baru terus-menerus, padahal seluruhnya menyerang akun yang sama:</p>
<pre>admin, Admin, ADMIN, aDmin, AdMiN, adMIN, ...</pre>
<p class="hint">Coba beberapa kandidat password dengan variasi kapitalisasi berbeda di form
di bawah untuk melihat counter-nya selalu "reset" ke 0/3.</p>
</details>

<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="admin"><br>
  <label>Password</label><br>
  <input type="text" name="password"><br>
  <button type="submit">Login</button>
</form>
<?php if ($msg): ?><div class="<?php echo strpos($msg,'berhasil')!==false ? 'result-box' : 'error-box'; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<h3>Counter percobaan per varian penulisan (data mentah)</h3>
<div class="creds-box"><?php
foreach (($db['attempts_by_username'] ?? []) as $k => $v) {
    echo htmlspecialchars($k) . ' => ' . (int)$v . "/3<br>";
}
?></div>

<?php include 'footer.php'; ?>
