<?php
$title = 'Lab 9: Stored XSS via SVG Upload';
$uploadDir = __DIR__ . '/data/uploads';
$error = ''; $uploaded_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    // VULNERABLE: tidak ada validasi content-type maupun magic byte di server sama sekali.
    // Atribut accept="image/*" pada <input type="file"> di bawah HANYA hint di sisi
    // browser untuk mempermudah UI (filter dialog pemilihan file) - gampang dilewati
    // (drag & drop, ubah lewat DevTools, atau kirim request upload langsung pakai
    // curl/Burp) dan server tidak pernah benar-benar memverifikasi isi file yang masuk.
    $name = time() . '_' . basename($_FILES['avatar']['name']);
    $dest = $uploadDir . '/' . $name;
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
        $uploaded_name = $name;
    } else {
        $error = 'Upload gagal.';
    }
}

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: form ini menerima file avatar apa saja tanpa validasi tipe konten atau
magic byte di server. File SVG sebenarnya adalah dokumen XML biasa dan boleh berisi tag
<code>&lt;script&gt;</code> atau atribut event handler seperti <code>onload</code> — begitu
file <code>.svg</code> dibuka <strong>langsung</strong> lewat URL (bukan lewat
<code>&lt;img src="..svg"&gt;</code>, yang di kebanyakan browser mem-sandbox script di
dalamnya), browser merender dan mengeksekusi script tersebut dengan origin situs ini,
lengkap dengan akses ke <code>document.cookie</code> milik situs ini. Simpan kode berikut
sebagai file <code>pwned.svg</code> di komputer kamu:</p>
<pre class="hint">&lt;svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.domain)"&gt;&lt;text y="20"&gt;pwned&lt;/text&gt;&lt;/svg&gt;</pre>
<p class="hint">Upload lewat form di bawah, lalu klik link "Lihat avatar saya (ukuran penuh)"
yang muncul setelah upload berhasil — itu membuka file SVG-nya langsung (bukan sebagai
<code>&lt;img&gt;</code>), sehingga <code>onload</code> di dalamnya dieksekusi.</p>
</details>

<?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($uploaded_name): ?>
<div class="success-box">Avatar tersimpan sebagai <?php echo htmlspecialchars($uploaded_name); ?>
&mdash; <a href="data/uploads/<?php echo rawurlencode($uploaded_name); ?>" target="_blank">Lihat avatar saya (ukuran penuh)</a></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>Avatar</label><br>
  <input type="file" name="avatar" accept="image/*"><br>
  <button type="submit">Upload Avatar</button>
</form>

<?php
$files = is_dir($uploadDir) ? array_diff(scandir($uploadDir), ['.', '..', '.gitkeep']) : [];
if ($files): ?>
<h3>Avatar tersimpan</h3>
<ul class="file-list">
<?php foreach ($files as $f): ?>
  <li><a href="data/uploads/<?php echo rawurlencode($f); ?>" target="_blank"><?php echo htmlspecialchars($f); ?></a></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php include 'footer.php'; ?>
