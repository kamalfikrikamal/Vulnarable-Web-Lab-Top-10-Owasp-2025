<?php
$title = 'Lab 4: Account Lockout Membocorkan Validitas Username';
include 'header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user = find_user($db, $username);

    if ($user) {
        // VULNERABLE: a failed-attempt counter (and lockout message) only
        // ever gets created for USERNAMES THAT EXIST. Trying to log in as a
        // nonexistent user never touches this counter at all, so the mere
        // presence/absence of a lockout message reveals validity.
        $count = ($db['lockouts'][$username] ?? 0);
        if ($count >= 3) {
            $error = 'Akun ini dikunci sementara karena terlalu banyak percobaan gagal.';
        } elseif ($user['password'] === $password) {
            $db['lockouts'][$username] = 0;
            save_db($db);
            $error = '';
        } else {
            $db['lockouts'][$username] = $count + 1;
            save_db($db);
            $error = 'Password salah. (' . (3 - ($count + 1)) . ' percobaan tersisa)';
        }
    } else {
        $error = 'Invalid username or password.';
    }
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: coba salah password 3x berturut-turut untuk username <code>alice</code>
(valid) &mdash; muncul pesan "akun dikunci". Coba juga 3x untuk username acak yang tidak
terdaftar (mis. <code>randomuser123</code>) &mdash; pesan lockout TIDAK PERNAH muncul untuk
username manapun yang tidak valid, karena counter percobaan hanya dibuat untuk username yang
memang ada. Perbedaan perilaku inilah oracle-nya.</p>
</details>

<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" value="alice"><br>
  <label>Password</label><br>
  <input type="password" name="password"><br>
  <button type="submit">Login</button>
</form>
<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<?php include 'footer.php'; ?>
