<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab File Upload Vulnerabilities mengikuti kategori PortSwigger Web Security
Academy: dari upload tanpa validasi sama sekali sampai teknik bypass validasi Content-Type,
path traversal pada nama file, blacklist ekstensi, null byte, polyglot, dan race condition.
Lihat <code>README.md</code> untuk payload dan penjelasan tiap lab.</p>

<p class="hint">Semua webshell yang berhasil di-upload akan bisa dieksekusi lewat parameter
<code>?cmd=...</code>, mis. <code>uploads/shell.php?cmd=id</code>.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Remote Code Execution via unrestricted upload</h3>
  <p>Tidak ada validasi ekstensi maupun konten sama sekali.</p>
  <a class="btn" href="lab1_unrestricted.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Web shell upload via Content-Type bypass</h3>
  <p>Validasi hanya mengandalkan header <code>Content-Type</code> yang dikirim client &mdash;
  sepenuhnya bisa dipalsukan.</p>
  <a class="btn" href="lab2_content_type.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Web shell upload via path traversal</h3>
  <p>Upload dibatasi ke folder yang tidak bisa mengeksekusi PHP, tapi nama file tidak
  disanitasi dari traversal.</p>
  <a class="btn" href="lab3_path_traversal.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Web shell upload via extension blacklist bypass</h3>
  <p>Blacklist ekstensi PHP tidak lengkap &mdash; lupa varian <code>.phtml</code>/<code>.pht</code>.</p>
  <a class="btn" href="lab4_blacklist_bypass.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 5 &mdash; Web shell upload via extension handling override (.htaccess)</h3>
  <p>Blacklist ekstensi sudah lengkap, tapi tidak menyangka nama file itu sendiri bisa jadi
  file konfigurasi Apache (<code>.htaccess</code>) yang mendefinisikan ulang ekstensi mana
  yang dieksekusi sebagai PHP.</p>
  <a class="btn" href="lab5_obfuscated_extension.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 6 &mdash; RCE via polyglot web shell upload</h3>
  <p>Validasi "isi file harus benar-benar gambar" bisa dilewati dengan file polyglot
  (header gambar valid + kode PHP di baliknya).</p>
  <a class="btn" href="lab6_polyglot.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 7 &mdash; Web shell upload via race condition</h3>
  <p>File disimpan dulu ke disk, baru divalidasi &amp; dihapus belakangan &mdash; ada jendela
  waktu di mana file berbahaya sempat bisa diakses.</p>
  <a class="btn" href="lab7_race_condition.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
