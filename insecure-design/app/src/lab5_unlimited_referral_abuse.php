<?php
$title = 'Lab 5: Unlimited Referral Abuse';
include 'header.php';

$ref_code_default = $db['referral']['code'];
$prefill_ref = $_GET['ref'] ?? $ref_code_default;
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ref = trim($_POST['ref'] ?? '');

    if ($username === '' || $email === '') {
        $err = 'Username dan email wajib diisi.';
    } elseif (strcasecmp($ref, $db['referral']['code']) !== 0) {
        $err = 'Kode referral tidak valid.';
    } else {
        // VULNERABLE: satu-satunya pengecekan "uniqueness" adalah string
        // username persis sama. Tidak ada verifikasi email, tidak ada batas
        // per-IP/per-session, tidak ada cap jumlah total referral.
        $already = false;
        foreach ($db['referral']['signups'] as $s) {
            if ($s['username'] === $username) { $already = true; break; }
        }

        if ($already) {
            $err = "Username \"" . htmlspecialchars($username) . "\" sudah pernah dipakai untuk mendaftar lewat kode referral ini.";
        } else {
            $bonus = $db['referral']['bonus_per_signup'];
            $db['referral']['signups'][] = [
                'username' => $username,
                'email' => $email,
                'bonus' => $bonus,
                'time' => date('Y-m-d H:i:s'),
            ];
            $db['referral']['wallet_balance'] += $bonus;
            save_db($db);
            $db = load_db();

            $msg = "Akun \"" . htmlspecialchars($username) . "\" berhasil daftar lewat kode referral " . htmlspecialchars($ref) . ". Alice mendapat bonus " . rupiah($bonus) . ".";
        }
    }
}

$signup_count = count($db['referral']['signups']);
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Program referral ini memberi bonus tetap ke pemilik kode (<code>alice</code>)
setiap ada pendaftaran baru yang memakai kode referralnya. Satu-satunya pengecekan "orang baru"
yang dilakukan server adalah membandingkan string <code>username</code> persis sama dengan yang
sudah pernah dipakai &mdash; tidak ada verifikasi email, tidak ada limit per-IP/per-session, tidak
ada batas total. Daftar berulang kali dengan username yang sedikit berbeda tiap kali
(<code>user1</code>, <code>user2</code>, <code>user3</code>, ...) memakai kode yang sama
(<code><?php echo htmlspecialchars($ref_code_default); ?></code>), lalu lihat saldo wallet alice
&amp; jumlah pendaftar terus naik.</p>
</details>

<?php if ($err): ?><div class="error-box"><?php echo $err; ?></div><?php endif; ?>
<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<p><strong>Saldo wallet alice (pemilik kode referral): <?php echo rupiah($db['referral']['wallet_balance']); ?></strong><br>
<strong>Total akun yang mendaftar lewat kode referral ini: <?php echo $signup_count; ?></strong></p>

<h3>Daftar Akun Baru &amp; Dapatkan Bonus Referral</h3>
<form method="post">
  <label>Username</label><br>
  <input type="text" name="username" placeholder="mis. user1"><br>
  <label>Email</label><br>
  <input type="text" name="email" placeholder="mis. user1@mail.test"><br>
  <label>Kode Referral</label><br>
  <input type="text" name="ref" value="<?php echo htmlspecialchars($prefill_ref); ?>">
  <button type="submit">Daftar</button>
</form>

<h3>Daftar Pendaftar via Kode Referral <?php echo htmlspecialchars($ref_code_default); ?></h3>
<table class="data-table">
<tr><th>Username</th><th>Email</th><th>Bonus untuk Alice</th><th>Waktu</th></tr>
<?php foreach (array_reverse($db['referral']['signups']) as $s): ?>
<tr>
  <td><?php echo htmlspecialchars($s['username']); ?></td>
  <td><?php echo htmlspecialchars($s['email']); ?></td>
  <td><?php echo rupiah($s['bonus']); ?></td>
  <td><?php echo htmlspecialchars($s['time']); ?></td>
</tr>
<?php endforeach; ?>
</table>

<?php include 'footer.php'; ?>
