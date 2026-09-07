<?php
$title = 'Lab 3: Signature Tidak Pernah Diverifikasi';
require_once __DIR__ . '/lib.php';

define('LAB3_SECRET', 'irrelevant-because-never-checked');

if (empty($_SESSION['lab3_token'])) {
    $_SESSION['lab3_token'] = jwt_issue_hs256(['username' => 'guest', 'role' => 'user'], LAB3_SECRET);
}

function verify_lab3($token) {
    $p = jwt_parts($token);
    if (!$p || !$p['payload']) return null;
    // VULNERABLE: the server decodes and trusts the payload straight away.
    // It never recomputes hash_hmac() over the signing input and compares
    // it against the signature segment - the signature could be anything,
    // including garbage, and this function would never notice.
    return $p['payload'];
}

$verify_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $payload = verify_lab3(trim($_POST['token']));
    if ($payload && ($payload['role'] ?? '') === 'admin') {
        $verify_result = 'Token diterima! Login sebagai admin (username: ' . htmlspecialchars($payload['username'] ?? '?') . ').';
    } elseif ($payload) {
        $verify_result = 'Token valid secara format, tapi role bukan admin.';
    } else {
        $verify_result = 'Format token tidak valid.';
    }
}

$forged_payload = '{"username":"admin","role":"admin"}';
$h_enc = b64url_encode('{"typ":"JWT","alg":"HS256"}');
$p_enc = b64url_encode($forged_payload);
$forged = "$h_enc.$p_enc.anything-goes-here-signature-is-never-checked";

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: kali ini server bahkan tidak repot-repot mengecek algoritma khusus &mdash;
fungsi verifikasinya cuma <code>base64_decode</code> bagian payload dan langsung mempercayainya.
Kamu tidak perlu tahu secret sama sekali (berbeda dari Lab 2), dan tidak perlu trik
<code>alg: none</code> (berbeda dari Lab 1) &mdash; cukup ubah payload token asli, isi bagian
signature dengan APA SAJA (bahkan string acak), dan server tetap menerimanya.</p>
<p class="hint">Payload hasil edit: <code><?php echo htmlspecialchars($forged_payload); ?></code></p>
<p class="hint">Token lengkap (signature sengaja diisi teks acak untuk membuktikan poinnya):</p>
<pre><?php echo $forged; ?></pre>
</details>

<p>Token asli kamu (role: user):</p>
<textarea readonly rows="3" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($_SESSION['lab3_token']); ?></textarea>

<h3>Verify Token</h3>
<form method="post">
  <textarea name="token" rows="3" style="width:100%;font-family:monospace;"><?php echo htmlspecialchars($forged); ?></textarea>
  <button type="submit">Verify &amp; Login</button>
</form>
<?php if ($verify_result): ?><div class="<?php echo strpos($verify_result,'admin (')!==false ? 'result-box' : 'error-box'; ?>"><?php echo $verify_result; ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
