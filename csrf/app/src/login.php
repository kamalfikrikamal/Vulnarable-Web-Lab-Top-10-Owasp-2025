<?php
require_once __DIR__ . '/lib.php';
$title = 'Login';

if (isset($_GET['logout'])) {
    $_SESSION['logged_in'] = false;
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simplified on purpose: this lab is about CSRF, not about credential
    // checking, so any submit logs the demo "victim" account in.
    $_SESSION['logged_in'] = true;
    header('Location: index.php');
    exit;
}

include 'header.php';
?>

<div class="creds-box">
Lab ini fokus ke CSRF, jadi login disederhanakan &mdash; klik tombol di bawah untuk masuk
sebagai akun demo <strong>victim</strong>. Bayangkan tab ini adalah tab tempat "korban" sedang
login ke aplikasi bank, sementara tab lain (halaman attacker) mencoba memicu aksi atas nama
korban.
</div>

<form method="post">
  <button type="submit">Login sebagai victim</button>
</form>

<?php include 'footer.php'; ?>
