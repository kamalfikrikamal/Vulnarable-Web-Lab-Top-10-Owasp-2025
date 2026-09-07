<?php
$title = 'Lab 1: Pesan Error Berbeda';
include 'header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user = find_user($db, $username);
    // VULNERABLE: two distinctly different error messages leak exactly
    // which validation step failed.
    if (!$user) {
        $error = 'User tidak ditemukan.';
    } elseif ($user['password'] !== $password) {
        $error = 'Password salah.';
    } else {
        $error = ''; // login sukses (di luar cakupan lab ini)
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: coba login dengan username acak (mis. <code>randomuser123</code>) dan
bandingkan pesannya dengan username <code>alice</code> yang memang terdaftar (password apa
saja). Dua pesan yang berbeda ini cukup untuk memetakan username mana saja yang valid, tanpa
perlu tahu passwordnya sama sekali.</p>
</details>

<form method="post">
  <label>Username</label><br>
  <input type="text" name="username"><br>
  <label>Password</label><br>
  <input type="password" name="password"><br>
  <button type="submit">Login</button>
</form>
<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
