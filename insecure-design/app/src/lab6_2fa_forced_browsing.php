<?php
$title = 'Lab 6: 2FA Bypass Lewat Forced Browsing';
include 'header.php';

$acc = $db['auth_account'];
$step = $_GET['step'] ?? 'login';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'login') {
    if (($_POST['username'] ?? '') === $acc['username'] && ($_POST['password'] ?? '') === $acc['password']) {
        // VULNERABLE: begitu password benar, sesi langsung ditandai "authenticated"
        // - padahal proses login SEHARUSNYA baru selesai setelah OTP juga diverifikasi.
        // Halaman "dashboard" di bawah cuma mengecek flag ini, tidak pernah mengecek
        // apakah OTP-nya benar-benar sudah diverifikasi.
        $_SESSION['la6_authenticated'] = true;
        $step = 'otp';
    } else {
        $error = 'Username atau password salah.';
        $step = 'login';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'otp') {
    if (($_POST['otp'] ?? '') === $acc['otp']) {
        $_SESSION['la6_otp_verified'] = true;
        $step = 'dashboard';
    } else {
        $error = 'Kode OTP salah.';
        $step = 'otp';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['la6_authenticated'], $_SESSION['la6_otp_verified']);
    $step = 'login';
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: masuk ke halaman <code>?step=dashboard</code> TANPA pernah memasukkan kode
OTP yang benar. Login dulu dengan <code>alice</code> / <code>Password123</code> (password sengaja
diberitahu di sini supaya fokus lab ke logika 2FA-nya, bukan menebak password) — server akan
mengarahkanmu ke step OTP. Alih-alih mengisi OTP, coba akses langsung
<code>?step=dashboard</code> lewat address bar.</p>
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

<?php elseif ($step === 'otp'): ?>
<h3>Step 2: Masukkan Kode OTP</h3>
<p>Kode OTP dikirim ke email/SMS terdaftar (di lab ini, sengaja TIDAK ditampilkan — kamu tidak
seharusnya tahu kodenya di titik ini).</p>
<form method="post">
  <input type="hidden" name="form" value="otp">
  <label>Kode OTP (6 digit)</label><br>
  <input type="text" name="otp" placeholder="......">
  <button type="submit">Verifikasi</button>
</form>
<p class="hint">...atau coba lewati step ini sama sekali. Ubah URL di address bar jadi
<code>?step=dashboard</code> dan tekan Enter.</p>

<?php elseif ($step === 'dashboard'): ?>
<?php if (empty($_SESSION['la6_authenticated'])): ?>
  <div class="error-box">Belum login. <a href="lab6_2fa_forced_browsing.php">Login dulu</a>.</div>
<?php else: ?>
  <div class="<?php echo empty($_SESSION['la6_otp_verified']) ? 'error-box' : 'ok-box'; ?>">
    <?php if (empty($_SESSION['la6_otp_verified'])): ?>
      <strong>⚠ Kamu masuk ke dashboard ini TANPA pernah memverifikasi OTP.</strong>
      Session <code>la6_authenticated</code> = true, tapi <code>la6_otp_verified</code> tidak
      pernah diset. Halaman ini seharusnya menolak akses, tapi cuma mengecek flag yang pertama.
    <?php else: ?>
      Login lengkap (password + OTP terverifikasi).
    <?php endif; ?>
  </div>
  <h3>Dashboard Akun — alice</h3>
  <table class="data-table">
    <tr><th>Saldo</th><td><?php echo rupiah(2450000); ?></td></tr>
    <tr><th>Kartu tersimpan</th><td>**** **** **** 4412</td></tr>
    <tr><th>Alamat</th><td>Jl. Merdeka No. 12, Jakarta</td></tr>
  </table>
<?php endif; ?>
<p><a href="?logout=1">Logout</a></p>
<?php endif; ?>

<p class="hint">Kenapa berhasil: server memakai DUA flag session terpisah untuk merepresentasikan
"password benar" (<code>la6_authenticated</code>) dan "OTP terverifikasi"
(<code>la6_otp_verified</code>) — desain ini sudah benar secara struktur. Bug-nya ada di halaman
dashboard: ia cuma mengecek flag PERTAMA sebelum memberi akses, padahal seharusnya login baru
dianggap lengkap kalau KEDUA flag itu true. Forced browsing (mengetik URL step lanjutan secara
langsung) melewati pengecekan yang seharusnya cuma "dipandu" lewat urutan halaman, bukan benar-benar
ditegakkan di setiap endpoint yang mengandalkannya.</p>

<?php include 'footer.php'; ?>
