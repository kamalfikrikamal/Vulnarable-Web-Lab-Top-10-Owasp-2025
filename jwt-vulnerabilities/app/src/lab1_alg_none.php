<?php
$title = 'Lab 1: Algoritma alg=none Diterima';
require_once __DIR__ . '/lib.php';

define('LAB1_SECRET', 'S3cr3t_Key_Lab1_Xy9!'); // never revealed to the "attacker"

if (empty($_SESSION['lab1_token'])) {
    $_SESSION['lab1_token'] = jwt_issue_hs256(['username' => 'guest', 'role' => 'user'], LAB1_SECRET);
}

function verify_lab1($token) {
    $p = jwt_parts($token);
    if (!$p || !$p['header']) return null;
    $alg = $p['header']['alg'] ?? '';
    if ($alg === 'HS256') {
        $expected = b64url_encode(hash_hmac('sha256', $p['signing_input'], LAB1_SECRET, true));
        return hash_equals($expected, $p['raw'][2] ?? '') ? $p['payload'] : null;
    }
    if ($alg === 'none') {
        // VULNERABLE: "none" is a legitimate value in the JWS spec meant for
        // situations where integrity is already guaranteed elsewhere - it
        // should NEVER be accepted for a token whose whole point is proving
        // authenticity. This server accepts it and trusts the payload as-is.
        return $p['payload'];
    }
    return null;
}

$verify_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $payload = verify_lab1(trim($_POST['token']));
    if ($payload && ($payload['role'] ?? '') === 'admin') {
        $verify_result = 'Token diterima! Login sebagai admin (username: ' . htmlspecialchars($payload['username'] ?? '?') . ').';
    } elseif ($payload) {
        $verify_result = 'Token valid, tapi role bukan admin (role: ' . htmlspecialchars($payload['role'] ?? '?') . ').';
    } else {
        $verify_result = 'Token ditolak: signature tidak valid.';
    }
}

$header_json = '{"typ":"JWT","alg":"none"}';
$payload_json = '{"username":"admin","role":"admin"}';
$h_enc = b64url_encode($header_json);
$p_enc = b64url_encode($payload_json);
$forged = "$h_enc.$p_enc.";

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: token normal kamu (role: user) ditandatangani HS256 dengan secret yang
tidak pernah diberitahukan ke kamu. Tapi fungsi verifikasi server juga menerima
<code>alg: "none"</code> &mdash; nilai yang secara spesifikasi JWS memang ada, tapi hanya boleh
dipakai dalam konteks yang integritasnya sudah dijamin lewat cara lain, <strong>bukan</strong>
untuk token otentikasi. Server ini keliru mempercayai payload token <code>alg: none</code>
begitu saja, tanpa signature apa pun.</p>
<p class="hint">Header JSON: <code><?php echo htmlspecialchars($header_json); ?></code><br>
Payload JSON: <code><?php echo htmlspecialchars($payload_json); ?></code></p>
<p class="hint">Base64URL masing-masing bagian (tanpa padding <code>=</code>, <code>+</code>&rarr;<code>-</code>, <code>/</code>&rarr;<code>_</code>):</p>
<ul class="hint">
<li>Header: <code><?php echo $h_enc; ?></code></li>
<li>Payload: <code><?php echo $p_enc; ?></code></li>
</ul>
<p class="hint">Gabungkan jadi <code>header.payload.</code> (perhatikan titik terakhir - bagian
signature sengaja dikosongkan karena <code>alg: none</code> tidak butuh signature):</p>
<pre><?php echo $forged; ?></pre>
</details>

<p>Token asli kamu (role: user):</p>
<textarea readonly rows="3" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($_SESSION['lab1_token']); ?></textarea>

<h3>Verify Token</h3>
<form method="post">
  <textarea name="token" rows="3" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($forged); ?></textarea>
  <button type="submit">Verify &amp; Login</button>
</form>
<?php if ($verify_result): ?><div class="<?php echo strpos($verify_result,'admin (')!==false ? 'result-box' : 'error-box'; ?>"><?php echo $verify_result; ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
