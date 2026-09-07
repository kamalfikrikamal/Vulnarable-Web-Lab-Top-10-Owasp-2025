<?php
require_once __DIR__ . '/lib.php';
$title = 'Login';
$db = load_db();
$error = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['uid']);
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user = find_user_by_username($db, $username);
    if ($user && $user['password'] === $password) {
        $_SESSION['uid'] = $user['id'];
        header('Location: index.php');
        exit;
    }
    $error = 'Username atau password salah.';
}

include 'header.php';
?>

<div class="creds-box">
Akun demo (dipublikasikan sengaja untuk keperluan lab): <code>alice/alice123</code>,
<code>bob/bob123</code>, <code>carol/carol123</code>, <code>admin/admin123</code>.
Login sebagai <strong>alice</strong> lalu coba akses data milik <strong>bob</strong>/<strong>carol</strong>/<strong>admin</strong> lewat parameter ID di tiap lab.
</div>

<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<form method="post">
  <label>Username</label><br>
  <input type="text" name="username"><br>
  <label>Password</label><br>
  <input type="password" name="password"><br>
  <button type="submit">Login</button>
</form>

<?php include 'footer.php'; ?>
