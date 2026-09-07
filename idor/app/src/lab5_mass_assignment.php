<?php
$title = 'Lab 5: Mass Assignment';
include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABLE: every posted field is merged straight into the user
    // record ("bind all attributes at once" instead of an explicit
    // allowlist of editable fields), including ones the UI never exposes
    // like "role".
    foreach ($db['users'] as &$u) {
        if ((string)$u['id'] === (string)$me['id']) {
            $u = array_merge($u, $_POST);
            $u['id'] = $me['id'];
            $me = $u;
            break;
        }
    }
    unset($u);
    save_db($db);
    $db = load_db();
    $msg = 'Profil berhasil diupdate.';
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: form ini cuma menampilkan field <code>full_name</code> dan <code>email</code>
untuk diedit. Tapi di server, seluruh isi <code>$_POST</code> di-merge langsung ke data user
(<code>array_merge($user, $_POST)</code>) tanpa allowlist field mana yang boleh diubah. Tambahkan
field baru ke request (lewat DevTools: ubah form sebelum submit, atau pakai Burp/curl) dengan
nama <code>role</code> berisi <code>admin</code> untuk menaikkan hak akses akunmu sendiri.</p>
<p class="hint">Contoh dengan curl (ganti cookie session sesuai punyamu):</p>
<pre>curl -b "PHPSESSID=..." -d "full_name=Alice&email=alice@corp.test&role=admin" http://target/lab5_mass_assignment.php</pre>
</details>

<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<p>Role kamu saat ini: <span class="badge <?php echo $me['role'] === 'admin' ? 'admin' : 'user'; ?>"><?php echo htmlspecialchars($me['role']); ?></span></p>

<form method="post">
  <label>Nama lengkap</label><br>
  <input type="text" name="full_name" value="<?php echo htmlspecialchars($me['full_name']); ?>"><br>
  <label>Email</label><br>
  <input type="text" name="email" value="<?php echo htmlspecialchars($me['email']); ?>"><br>
  <button type="submit">Update Profil</button>
</form>

<?php include 'footer.php'; ?>
