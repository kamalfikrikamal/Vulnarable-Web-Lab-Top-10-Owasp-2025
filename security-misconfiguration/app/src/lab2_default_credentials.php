<?php
$title = 'Lab 2: Default Credentials';
require_once __DIR__ . '/lib.php';
$db = load_db();

$login_msg = '';
$logged_in_admin = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    // VULNERABLE: kredensial default vendor (admin/admin123) tidak pernah
    // diganti sebelum aplikasi go-live ke production.
    if ($u === $db['admin_username'] && $p === $db['admin_password']) {
        $_SESSION['admin_logged_in'] = true;
        $logged_in_admin = true;
    } else {
        // Pesan gagal generik dengan sengaja - tidak membocorkan apakah
        // username atau password yang salah, supaya fokus lab tetap di
        // masalah kredensial default, bukan user enumeration.
        $login_msg = 'Login gagal. Periksa kembali username dan password.';
    }
}

if (!empty($_SESSION['admin_logged_in'])) {
    $logged_in_admin = true;
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: lab2_default_credentials.php');
    exit;
}

include 'header.php';
?>

<!-- TODO: ganti kredensial default admin/admin123 sebelum go-live -->

<p>Panel login admin di bawah ini memakai kredensial bawaan (default) dari installer/vendor
aplikasi ini. Tim yang men-deploy lupa (atau tidak sempat) menggantinya dengan kredensial yang
unik sebelum sistem dipakai secara nyata.</p>

<?php if (!$logged_in_admin): ?>
<h3>Admin Login</h3>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value=""><br>
  <label>Password</label><br>
  <input type="password" name="password" value="">
  <button type="submit" name="login" value="1">Login</button>
</form>
<?php if ($login_msg): ?>
<div class="error-box"><?php echo htmlspecialchars($login_msg); ?></div>
<?php endif; ?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Banyak aplikasi/perangkat datang dengan akun admin bawaan untuk setup awal —
seringnya polanya sangat umum dan terdokumentasi publik (manual vendor, forum, dsb). Coba
kredensial default yang paling umum untuk panel admin: <code>admin</code> / <code>admin123</code>.</p>
</details>
<?php else: ?>
<div class="ok-box">Login berhasil sebagai admin.</div>
<h3>Admin Dashboard</h3>
<table class="data-table">
  <tr><th>ID</th><th>Username</th><th>Email</th></tr>
  <?php foreach ($db['users'] as $u): ?>
  <tr><td><?php echo (int)$u['id']; ?></td><td><?php echo htmlspecialchars($u['username']); ?></td><td><?php echo htmlspecialchars($u['email']); ?></td></tr>
  <?php endforeach; ?>
</table>
<p class="hint">Ini adalah data seluruh user terdaftar — di aplikasi nyata, panel admin seperti
ini biasanya juga punya kontrol untuk mengubah data, menghapus akun, export data, dsb. Semua
bisa diakses hanya karena kredensial default tidak pernah diganti.</p>
<p><a href="lab2_default_credentials.php?logout=1">Logout</a></p>
<?php endif; ?>

<?php include 'footer.php'; ?>
