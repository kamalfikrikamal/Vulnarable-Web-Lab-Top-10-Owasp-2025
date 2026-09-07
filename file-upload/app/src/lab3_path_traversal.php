<?php
$title = 'Lab 3: Web Shell Upload via Path Traversal';
$error = ''; $uploaded_path = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // "Aman" secara desain: file avatar ditaruh di uploads_safe/, direktori yang eksekusi
    // PHP-nya SUDAH DIMATIKAN lewat konfigurasi Apache (lihat apache/uploads_safe.conf).
    // VULNERABLE: nama file dipakai mentah tanpa basename()/sanitasi, jadi "../" di dalamnya
    // tetap dihormati oleh filesystem dan bisa membawa file keluar dari uploads_safe/.
    // CATATAN PHP 8.1+: $_FILES['file']['name'] SEKARANG otomatis di-basename() oleh PHP
    // sendiri (path aslinya dipindah ke field baru 'full_path', ditambahkan untuk mendukung
    // upload folder lewat <input webkitdirectory>). Kode di bawah ini memakai 'full_path'
    // dengan fallback ke 'name' - persis pola yang dipakai developer yang meniru kode lama
    // atau sengaja butuh path asli, sehingga kerentanannya tetap bisa direproduksi.
    $name = $_FILES['file']['full_path'] ?? $_FILES['file']['name'];
    $dest = __DIR__ . '/uploads_safe/' . $name;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $uploaded_path = 'uploads_safe/' . $name;
    } else {
        $error = 'Upload failed.';
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: form ini untuk upload avatar, disimpan ke <code>uploads_safe/</code>
&mdash; folder yang PHP-nya sengaja dimatikan (coba upload <code>shell.php</code> normal ke
sini, lalu akses langsung, hasilnya <strong>403 Forbidden</strong>, bukan RCE). Tapi nama file
tidak disanitasi dari traversal, dan aplikasi ini juga punya folder <code>uploads/</code> yang
PHP-nya <strong>aktif</strong> (dipakai lab lain). Karena field nama file di request multipart
sepenuhnya bisa dikontrol lewat proxy seperti Burp Repeater (tidak terbatas pada apa yang
diketik di dialog pemilih file browser), sisipkan traversal pada nama file supaya file
mendarat di <code>uploads/</code>, bukan <code>uploads_safe/</code>:</p>
<pre class="hint">Content-Disposition: form-data; name="file"; filename="../uploads/shell.php"</pre>
<p class="hint">Lalu akses <code>uploads/shell.php?cmd=id</code> (bukan lewat
<code>uploads_safe/</code>).</p>
</details>

<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($uploaded_path): ?>
<div class="success-box">Uploaded to <?php echo htmlspecialchars($uploaded_path); ?>
&mdash; <a href="<?php echo htmlspecialchars($uploaded_path); ?>?cmd=id" target="_blank">buka &amp; coba ?cmd=id</a></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>Avatar file</label><br>
  <input type="file" name="file"><br>
  <button type="submit">Upload Avatar</button>
</form>

<?php
$safe_files = array_diff(scandir(__DIR__ . '/uploads_safe'), ['.', '..']);
$exec_files = array_diff(scandir(__DIR__ . '/uploads'), ['.', '..']);
?>
<h3>uploads_safe/ (PHP execution disabled)</h3>
<ul class="file-list">
<?php foreach ($safe_files as $f): ?>
  <li><a href="uploads_safe/<?php echo rawurlencode($f); ?>" target="_blank"><?php echo htmlspecialchars($f); ?></a></li>
<?php endforeach; ?>
</ul>
<h3>uploads/ (PHP execution enabled &mdash; shared dengan lab lain)</h3>
<ul class="file-list">
<?php foreach ($exec_files as $f): ?>
  <li><a href="uploads/<?php echo rawurlencode($f); ?>" target="_blank"><?php echo htmlspecialchars($f); ?></a></li>
<?php endforeach; ?>
</ul>

<?php include 'footer.php'; ?>
