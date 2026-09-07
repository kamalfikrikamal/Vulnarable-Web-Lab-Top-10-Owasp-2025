<?php
$title = 'Lab 5: Extension Handling Override via .htaccess';
$error = ''; $uploaded_name = '';
// Blacklist ekstensi script yang cukup lengkap (mencakup semua yang dilewatkan Lab 4) -
// TAPI tidak pernah mempertimbangkan bahwa nama file ITU SENDIRI bisa berupa file
// KONFIGURASI web server (.htaccess), bukan cuma "gambar" atau "script".
$blacklist = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $name = basename($_FILES['file']['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (in_array($ext, $blacklist, true)) {
        $error = "Extension .$ext is blacklisted.";
    } else {
        // VULNERABLE: direktori uploads/ ini punya AllowOverride All (lihat konfigurasi
        // Apache bawaan php:8.2-apache), jadi FILE APA PUN bernama ".htaccess" yang berhasil
        // tersimpan di sini akan langsung dihormati oleh Apache untuk request berikutnya -
        // termasuk mendefinisikan ulang ekstensi mana yang dieksekusi sebagai PHP.
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
<p class="hint">Goal: blacklist ekstensi kali ini sudah cukup lengkap (mencakup
<code>.phtml</code>/<code>.pht</code> yang jadi celah di Lab 4) - tapi tidak pernah berpikir
bahwa nama file yang di-upload bisa jadi file KONFIGURASI Apache itu sendiri. Direktori
<code>uploads/</code> punya <code>AllowOverride All</code> aktif (default pada image
<code>php:8.2-apache</code>), jadi <code>.htaccess</code> yang berhasil ter-upload akan
langsung berlaku:</p>
<ol class="hint">
  <li>Upload file bernama persis <code>.htaccess</code> berisi:
  <pre class="hint">AddType application/x-httpd-php .jpg</pre>
  (ekstensi "file" ini secara teknis adalah <code>htaccess</code>, bukan salah satu yang
  diblokir, jadi lolos validasi)</li>
  <li>Upload file kedua <code>shell.jpg</code> berisi kode PHP biasa (ekstensi
  <code>.jpg</code> juga jelas tidak ada di blacklist manapun)</li>
  <li>Akses <code>uploads/shell.jpg?cmd=id</code> - Apache sekarang memperlakukan
  <code>.jpg</code> sebagai PHP, mengikuti <code>.htaccess</code> yang baru saja
  di-upload attacker sendiri.</li>
</ol>
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
