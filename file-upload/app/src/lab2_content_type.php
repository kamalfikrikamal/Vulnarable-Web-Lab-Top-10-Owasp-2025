<?php
$title = 'Lab 2: Content-Type Restriction Bypass';
$error = ''; $uploaded_name = '';
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // VULNERABLE: hanya memeriksa $_FILES['file']['type'], yang diambil MENTAH dari header
    // Content-Type bagian multipart yang dikirim CLIENT sendiri - tidak pernah divalidasi
    // ulang di server (mis. lewat magic bytes), jadi sepenuhnya bisa dipalsukan.
    if (in_array($_FILES['file']['type'], $allowed_types, true)) {
        $name = basename($_FILES['file']['name']);
        $dest = __DIR__ . '/uploads/' . $name;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            $uploaded_name = $name;
        } else {
            $error = 'Upload failed.';
        }
    } else {
        $error = 'Invalid file type: ' . htmlspecialchars($_FILES['file']['type']) . '. Only image/jpeg, image/png, image/gif allowed.';
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: validasi hanya mengandalkan header <code>Content-Type</code> pada bagian
multipart request - nilai ini sepenuhnya dikendalikan client, terlepas dari isi/ekstensi file
yang sesungguhnya. Upload <code>shell.php</code> tapi paksa Content-Type-nya jadi
<code>image/png</code>:</p>
<pre class="hint">curl -s -F "file=@shell.php;type=image/png" http://localhost:8079/upload/lab2_content_type.php</pre>
<p class="hint">Atau lewat Burp Repeater: intercept upload biasa, ubah
<code>Content-Type: image/png</code> pada bagian file di body multipart, biarkan nama file
&amp; isinya tetap <code>shell.php</code> / kode PHP.</p>
</details>

<?php if ($error): ?><div class="error-box"><?php echo $error; ?></div><?php endif; ?>
<?php if ($uploaded_name): ?>
<div class="success-box">Uploaded to uploads/<?php echo htmlspecialchars($uploaded_name); ?>
&mdash; <a href="uploads/<?php echo rawurlencode($uploaded_name); ?>?cmd=id" target="_blank">buka &amp; coba ?cmd=id</a></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>File (harus tampak seperti image/jpeg, image/png, atau image/gif)</label><br>
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
