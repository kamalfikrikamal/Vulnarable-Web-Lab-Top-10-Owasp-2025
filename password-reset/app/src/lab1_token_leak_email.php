<?php
$title = 'Lab 1: Token Reset Bocor Lewat Tracking Pixel Email';
require_once __DIR__ . '/lib.php';
$db = load_db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $username = $_POST['username'] ?? '';
    if (find_user($db, $username)) {
        $token = bin2hex(random_bytes(8));
        $db['reset_tokens'][$token] = ['username' => $username, 'used' => false];
        $reset_link = "lab1_token_leak_email.php?confirm=1&token=$token";
        // VULNERABLE: the transactional email includes a third-party open-
        // tracking pixel that carries the SAME reset token in its own URL -
        // completely independent of the (arguably fine) reset link itself.
        $email_html = "<p>Halo $username,</p><p>Klik link berikut untuk reset password: <a href=\"$reset_link\">$reset_link</a></p>" .
                      "<img src=\"mail_tracker_beacon.php?leaked_token=$token\" width=\"1\" height=\"1\">";
        $db['email_log'][] = ['to' => $username, 'html' => $email_html];
        $db['email_log'] = array_slice($db['email_log'], -3);
        save_db($db);
        $msg = 'Email reset password "terkirim" ke ' . htmlspecialchars($username) . ' (lihat simulasi inbox di bawah).';
    } else {
        $msg = 'Jika akun ada, email reset telah dikirim.';
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
<p class="hint">Goal: request reset untuk <code>admin</code>, lalu lihat "Simulasi Inbox" di
bawah &mdash; email tersebut merender sebuah gambar 1x1 dari domain analytics email pihak
ketiga (disimulasikan lewat <code>mail_tracker_beacon.php</code>), dan URL gambar itu membawa
token reset yang sama persis dengan yang ada di link reset resminya. Begitu email dibuka
(gambar dirender otomatis oleh klien email/webmail preview), token bocor ke pihak ketiga itu
tanpa korban maupun aplikasi sadar. Scroll ke bawah untuk lihat "Log Tracker Pihak Ketiga",
salin token dari sana, lalu pakai di form "Confirm Reset" untuk mengganti password admin.</p>
</details>

<h3>1. Request Reset</h3>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="admin">
  <button type="submit" name="request_reset" value="1">Request Password Reset</button>
</form>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<h3>Simulasi Inbox (yang dilihat korban)</h3>
<?php foreach (($db['email_log'] ?? []) as $mail): ?>
<div class="result-box" style="color:#111;background:#fff;"><?php echo $mail['html']; ?></div>
<?php endforeach; ?>

<h3>Log Tracker Pihak Ketiga (mailtracker.example.com)</h3>
<div class="result-box"><?php
$log = $db['tracker_log'] ?? [];
echo $log ? htmlspecialchars(implode("\n", $log)) : '(belum ada data - buka halaman ini sekali lagi setelah request reset, agar gambar sempat dimuat)';
?></div>

<h3>2. Confirm Reset (sebagai attacker, pakai token yang bocor)</h3>
<form method="post" action="lab1_token_leak_email.php?confirm=1">
  <label>Token</label><br>
  <input type="text" name="token" placeholder="tempel token dari log tracker">
  <label>Password baru</label><br>
  <input type="text" name="new_password" value="hacked123">
  <button type="submit">Reset Password</button>
</form>
<?php if ($confirm_msg): ?><div class="<?php echo strpos($confirm_msg,'berhasil')!==false ? 'result-box' : 'error-box'; ?>"><?php echo htmlspecialchars($confirm_msg); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
