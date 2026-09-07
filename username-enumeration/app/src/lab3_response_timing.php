<?php
$title = 'Lab 3: Perbedaan Waktu Respons';
include 'header.php';

$error = '';
$elapsed = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $start = microtime(true);
    $user = find_user($db, $username);
    if (!$user) {
        // Username doesn't exist - returns immediately without doing any
        // password comparison work at all.
        $error = 'Invalid username or password.';
    } else {
        // VULNERABLE: only reached when the username IS valid. Simulates
        // the cost of a real password hash check (bcrypt/Argon2 are
        // intentionally slow) - which becomes an observable timing oracle
        // for username validity, independent of the password's content.
        usleep(300000);
        $error = ($user['password'] === $password) ? '' : 'Invalid username or password.';
    }
    $elapsed = round(microtime(true) - $start, 3);
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: pesan error di sini SELALU identik (<code>Invalid username or
password.</code>) terlepas dari mana yang salah &mdash; tidak ada perbedaan pesan sama sekali.
Tapi waktu respons berbeda: username valid memicu proses verifikasi password yang mensimulasikan
cost hashing asli (di sini: <code>usleep(300000)</code>, 0.3 detik), sementara username tidak
valid langsung dijawab tanpa delay itu. Waktu respons diukur &amp; ditampilkan di bawah untuk
lab ini (di dunia nyata, kamu akan mengukurnya sendiri lewat Burp Intruder + Response Timer,
atau <code>time curl ...</code>, idealnya diulang beberapa kali untuk meredam noise jaringan).</p>
</details>

<form method="post">
  <label>Username</label><br>
  <input type="text" name="username"><br>
  <label>Password</label><br>
  <input type="password" name="password"><br>
  <button type="submit">Login</button>
</form>
<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($elapsed !== null): ?><p class="hint">Waktu respons server: <?php echo $elapsed; ?>s</p><?php endif; ?>

<?php include 'footer.php'; ?>
