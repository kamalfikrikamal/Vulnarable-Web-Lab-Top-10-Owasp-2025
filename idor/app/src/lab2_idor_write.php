<?php
$title = 'Lab 2: IDOR pada aksi tulis';
include 'header.php';

if (!$me) { require_login_notice(); include 'footer.php'; exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = $_POST['user_id'] ?? $me['id'];
    $new_email = $_POST['email'] ?? '';
    // VULNERABLE: user_id comes from a hidden form field, not from the
    // session. Nothing checks that $target_id === $me['id'] before writing.
    foreach ($db['users'] as &$u) {
        if ((string)$u['id'] === (string)$target_id) {
            $u['email'] = $new_email;
            $msg = "Email untuk user_id=$target_id berhasil diubah menjadi " . htmlspecialchars($new_email);
            break;
        }
    }
    unset($u);
    save_db($db);
    $db = load_db();
}
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: form ini "seharusnya" cuma mengubah email akunmu sendiri
(<code>user_id=<?php echo (int)$me['id']; ?></code>), dan memang begitu tampilannya di form &mdash;
tapi field <code>user_id</code> dikirim sebagai <em>hidden input</em> yang bisa kamu ubah bebas
lewat DevTools/Burp sebelum submit. Ubah nilainya jadi id user lain (mis. <code>4</code> untuk
akun admin) untuk mengubah email <strong>mereka</strong>, bukan email milikmu.</p>
</details>

<?php if ($msg): ?><div class="ok-box"><?php echo $msg; ?></div><?php endif; ?>

<form method="post">
  <input type="hidden" name="user_id" value="<?php echo (int)$me['id']; ?>">
  <label>Email baru untuk akunmu</label><br>
  <input type="text" name="email" value="<?php echo htmlspecialchars($me['email']); ?>">
  <button type="submit">Update Email</button>
</form>

<h3>Data user saat ini</h3>
<table class="data-table">
<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>
<?php foreach ($db['users'] as $u): ?>
<tr><td><?php echo (int)$u['id']; ?></td><td><?php echo htmlspecialchars($u['username']); ?></td><td><?php echo htmlspecialchars($u['email']); ?></td><td><?php echo htmlspecialchars($u['role']); ?></td></tr>
<?php endforeach; ?>
</table>

<?php include 'footer.php'; ?>
