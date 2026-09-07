<?php
require_once __DIR__ . '/lib.php';
$title = 'Login';
$db = load_db();
$error = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['uid']);
    setcookie('role', '', time() - 3600, '/');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user = find_user_by_username($db, $username);
    if ($user && $user['password'] === $password) {
        $_SESSION['uid'] = $user['id'];
        // Convenience cookie so lab3 has something client-writable to abuse -
        // this mirrors real apps that "cache" role info in a cookie for the UI.
        setcookie('role', $user['role'], 0, '/');
        header('Location: index.php');
        exit;
    }
    $error = 'Username atau password salah.';
}

include 'header.php';
?>

<div class="creds-box">
Akun demo: <code>alice/alice123</code> (user biasa), <code>bob/bob123</code> (user biasa),
<code>admin/admin123</code> (admin). Login sebagai <strong>alice</strong> untuk mencoba menaikkan
hak akses ke admin lewat tiap lab.
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
