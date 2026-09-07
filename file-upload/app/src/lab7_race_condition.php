<?php
$title = 'Lab 7: Web Shell Upload via Race Condition';
$error = ''; $result = '';
// Diperlambat SENGAJA (3 detik, jauh lebih lambat dari race window sungguhan yang biasanya
// hanya beberapa milidetik) supaya jendela race-nya bisa didemonstrasikan secara manual di
// kelas tanpa perlu tooling khusus seperti Burp Turbo Intruder.
const PROCESSING_DELAY_SECONDS = 3;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $name = basename($_FILES['file']['name']);
    $dest = __DIR__ . '/uploads/' . $name;

    // VULNERABLE: file DISIMPAN DULU ke direktori yang bisa dieksekusi PHP, baru DIVALIDASI
    // ISINYA belakangan (mis. simulasi "antivirus scan" / image re-encode job). Selama jeda
    // ini, file sudah bisa diakses & dieksekusi lewat URL langsung - inilah race window-nya.
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        sleep(PROCESSING_DELAY_SECONDS); // simulasi proses validasi/scan yang lambat
        $is_valid_image = @getimagesize($dest) !== false;
        if (!$is_valid_image) {
            @unlink($dest);
            $result = "File uploaded, processed for " . PROCESSING_DELAY_SECONDS . "s, then REJECTED & deleted (not a valid image).";
        } else {
            $result = "File uploaded and accepted: uploads/$name";
        }
    } else {
        $error = 'Upload failed.';
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: file di-<code>move_uploaded_file()</code> langsung ke
<code>uploads/</code> (folder yang PHP-nya aktif), BARU divalidasi isinya (dan dihapus kalau
tidak valid) setelah jeda <?php echo PROCESSING_DELAY_SECONDS; ?> detik. Selama jeda itu, file
sudah live dan bisa dieksekusi. Upload <code>shell.php</code> lewat form di bawah, lalu
<strong>secepatnya</strong> (dalam <?php echo PROCESSING_DELAY_SECONDS; ?> detik, di tab/
terminal lain) akses:</p>
<pre class="hint">curl "http://localhost:8079/upload/uploads/shell.php?cmd=id"</pre>
<p class="hint">Di dunia nyata jendela race biasanya hanya beberapa milidetik dan butuh
mengirim request upload &amp; request akses secara <strong>konkuren</strong> (mis. lewat Burp
Turbo Intruder atau beberapa proses <code>curl</code> paralel) supaya request akses tiba
tepat di antara <code>move_uploaded_file()</code> dan proses hapus. Lab ini melebar-lebarkan
jendelanya jadi <?php echo PROCESSING_DELAY_SECONDS; ?> detik supaya bisa didemonstrasikan
manual di kelas.</p>
</details>

<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($result): ?><div class="result-box"><?php echo htmlspecialchars($result); ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>File</label><br>
  <input type="file" name="file"><br>
  <button type="submit">Upload (akan diproses <?php echo PROCESSING_DELAY_SECONDS; ?>s sebelum divalidasi)</button>
</form>

<?php
$files = array_diff(scandir(__DIR__ . '/uploads'), ['.', '..']);
if ($files): ?>
<h3>uploads/ saat ini</h3>
<ul class="file-list">
<?php foreach ($files as $f): ?>
  <li><a href="uploads/<?php echo rawurlencode($f); ?>" target="_blank"><?php echo htmlspecialchars($f); ?></a></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php include 'footer.php'; ?>
