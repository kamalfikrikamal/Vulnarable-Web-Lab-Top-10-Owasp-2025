<?php
$title = 'Lab 8: Trusted Device Bypass 2FA';
include 'header.php';

$acc = $db['auth_account'];
$step = $_GET['step'] ?? 'login';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'login') {
    if (($_POST['username'] ?? '') === $acc['username'] && ($_POST['password'] ?? '') === $acc['password']) {
        $_SESSION['la8_authenticated'] = true;
        // VULNERABLE: "device sudah dipercaya" ditentukan HANYA dari cookie
        // trusted_device yang isinya cuma username polos - tanpa random
        // secret, tanpa hash, tanpa binding ke session/browser/device ID
        // yang sebenarnya. Siapa pun yang tahu/menebak/menyalin nilai cookie
        // ini (sama saja dengan menebak username) bisa melewati OTP sama
        // sekali, di device manapun.
        if (($_COOKIE['trusted_device'] ?? '') === $acc['username']) {
            $_SESSION['la8_otp_verified'] = true;
            $step = 'dashboard';
        } else {
            $step = 'otp';
        }
    } else {
        $error = 'Username atau password salah.';
        $step = 'login';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'otp') {
    if (($_POST['otp'] ?? '') === $acc['otp']) {
        $_SESSION['la8_otp_verified'] = true;
        if (!empty($_POST['trust_device'])) {
            setcookie('trusted_device', $acc['username'], time() + 30 * 86400, '/');
        }
        $step = 'dashboard';
    } else {
        $error = 'Kode OTP salah.';
        $step = 'otp';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['la8_authenticated'], $_SESSION['la8_otp_verified']);
    $step = 'login';
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: masuk ke <code>?step=dashboard</code> tanpa pernah mengisi OTP yang benar
— kali ini dashboard-nya SUDAH mengecek kedua flag session dengan benar (beda dari Lab 6), jadi
forced browsing biasa tidak akan berhasil di sini. Perhatikan fitur "Percayai perangkat ini 30
hari" di step OTP: coba tebak, tanpa pernah menyelesaikan OTP sekalipun, cookie macam apa yang
kira-kira dipasang fitur itu — lalu set sendiri cookie <code>trusted_device</code> itu lewat
DevTools SEBELUM login sama sekali, dan login ulang.</p>
</details>

<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<?php if ($step === 'login'): ?>
<h3>Step 1: Login</h3>
<form method="post">
  <input type="hidden" name="form" value="login">
  <label>Username</label><br>
  <input type="text" name="username" value="alice"><br>
  <label>Password</label><br>
  <input type="password" name="password" value="Password123"><br>
  <button type="submit">Login</button>
</form>
<p class="hint">Cookie <code>trusted_device</code> saat ini di browser kamu:
<code><?php echo htmlspecialchars($_COOKIE['trusted_device'] ?? '(tidak ada)'); ?></code></p>

<?php elseif ($step === 'otp'): ?>
<h3>Step 2: Masukkan Kode OTP</h3>
<form method="post">
  <input type="hidden" name="form" value="otp">
  <label>Kode OTP (6 digit)</label><br>
  <input type="text" name="otp" placeholder="......"><br>
  <label><input type="checkbox" name="trust_device" value="1" style="width:auto;"> Percayai perangkat ini selama 30 hari (lewati OTP di login berikutnya)</label><br>
  <button type="submit">Verifikasi</button>
</form>

<?php elseif ($step === 'dashboard'): ?>
<?php if (empty($_SESSION['la8_authenticated']) || empty($_SESSION['la8_otp_verified'])): ?>
  <div class="error-box">Belum login lengkap. <a href="lab8_trusted_device_bypass.php">Login dulu</a>.</div>
<?php else: ?>
  <div class="ok-box">Login berhasil (dashboard di lab ini konsisten mengecek KEDUA flag session).</div>
  <h3>Dashboard Akun — alice</h3>
  <table class="data-table">
    <tr><th>Saldo</th><td><?php echo rupiah(2450000); ?></td></tr>
    <tr><th>Kartu tersimpan</th><td>**** **** **** 4412</td></tr>
  </table>
<?php endif; ?>
<p><a href="?logout=1">Logout</a></p>
<?php endif; ?>

<p class="hint">Kenapa berhasil: fitur "trusted device" seharusnya menyimpan token ACAK (CSPRNG,
lihat kategori Insecure Randomness) yang di-generate SETELAH OTP benar-benar diverifikasi, lalu
diikat ke identitas device/session tertentu di server (disimpan di database, bisa dicabut per
device). Implementasi di lab ini cuma menaruh <strong>username polos</strong> sebagai nilai
cookie — bukan token rahasia sama sekali, gampang ditebak (sama saja dengan tahu username-nya),
dan tidak pernah benar-benar diverifikasi pernah "lulus" OTP di server manapun. Siapa pun yang
tahu/menebak username korban bisa memasang cookie itu sendiri di browser manapun, kapan pun,
tanpa pernah menyentuh proses OTP sama sekali.</p>

<?php include 'footer.php'; ?>
