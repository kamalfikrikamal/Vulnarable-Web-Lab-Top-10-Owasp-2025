<?php
$title = 'Lab 2: Secret HMAC Lemah';
require_once __DIR__ . '/lib.php';

define('LAB2_SECRET', 'letmein123'); // short, in every common wordlist - never shown directly

if (empty($_SESSION['lab2_token'])) {
    $_SESSION['lab2_token'] = jwt_issue_hs256(['username' => 'guest', 'role' => 'user'], LAB2_SECRET);
}

$forged = '';
$verify_result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forge'])) {
    $secret_guess = $_POST['secret'] ?? '';
    $username = $_POST['username'] ?: 'admin';
    $role = $_POST['role'] ?: 'admin';
    $forged = jwt_issue_hs256(['username' => $username, 'role' => $role], $secret_guess);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = trim($_POST['token']);
    $p = jwt_parts($token);
    if ($p) {
        // VULNERABLE: the algorithm itself (HS256) is fine here - the flaw
        // is the SECRET being short and guessable, which makes offline
        // brute force (hashcat -m 16500 / jwt_tool -C -d wordlist.txt)
        // completely practical.
        $expected = b64url_encode(hash_hmac('sha256', $p['signing_input'], LAB2_SECRET, true));
        if (hash_equals($expected, $p['raw'][2] ?? '') && ($p['payload']['role'] ?? '') === 'admin') {
            $verify_result = 'Token diterima! Login sebagai admin (username: ' . htmlspecialchars($p['payload']['username'] ?? '?') . ').';
        } elseif (hash_equals($expected, $p['raw'][2] ?? '')) {
            $verify_result = 'Token valid, tapi role bukan admin.';
        } else {
            $verify_result = 'Token ditolak: signature tidak valid (secret yang kamu tebak salah).';
        }
    } else {
        $verify_result = 'Format token tidak valid.';
    }
}

$wordlist = ['123456', 'secret', 'changeme', 'password123', 'letmein123', 'qwerty123', 'admin2024', 'topsecret', 'jwtsecret', 'company123'];

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: token ditandatangani HS256 dengan secret pendek yang bisa ditemukan di
wordlist umum untuk JWT (mis. daftar bawaan <code>jwt_tool</code> atau mode hashcat
<code>-m 16500</code>). Gunakan tool "Forge Token" di bawah untuk mencoba tiap kandidat secret
dari wordlist &mdash; kalau tebakanmu benar, token forge-anmu akan diterima server sebagai
admin.</p>
<p class="hint">Di dunia nyata: <code>hashcat -m 16500 token.txt wordlist.txt</code> mencoba
jutaan kandidat per detik secara offline, tanpa perlu menyentuh server sama sekali sampai
secret ditemukan.</p>
</details>

<p>Token asli kamu (role: user):</p>
<textarea readonly rows="3" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($_SESSION['lab2_token']); ?></textarea>

<h3>Wordlist kandidat secret</h3>
<div class="creds-box"><?php echo implode(', ', array_map('htmlspecialchars', $wordlist)); ?></div>

<h3>Forge Token (coba tiap kandidat secret)</h3>
<form method="post">
  <label>Secret candidate</label><br>
  <input type="text" name="secret" value="letmein123"><br>
  <label>Username</label><br>
  <input type="text" name="username" value="admin"><br>
  <label>Role</label><br>
  <input type="text" name="role" value="admin"><br>
  <button type="submit" name="forge" value="1">Forge Token</button>
</form>
<?php if ($forged): ?><textarea readonly rows="3" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($forged); ?></textarea><?php endif; ?>

<h3>Verify Token</h3>
<form method="post">
  <textarea name="token" rows="3" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($forged); ?></textarea>
  <button type="submit">Verify &amp; Login</button>
</form>
<?php if ($verify_result): ?><div class="<?php echo strpos($verify_result,'admin (')!==false ? 'result-box' : 'error-box'; ?>"><?php echo $verify_result; ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
