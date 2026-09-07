<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kegagalan penyimpanan password (bagian dari Cryptographic Failures): password
disimpan dengan cara yang membuatnya mudah dipulihkan kembali ke bentuk asli jika database
bocor &mdash; baik karena disimpan mentah, di-hash dengan algoritma cepat & tanpa salt, atau
cuma "disamarkan" (encoding) alih-alih benar-benar dienkripsi/di-hash.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Penyimpanan password plaintext</h3>
  <p>Password disimpan apa adanya di database, terbaca langsung kalau database bocor.</p>
  <a class="btn" href="lab1_plaintext.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Hash MD5 tanpa salt</h3>
  <p>Password di-hash pakai MD5 tanpa salt &mdash; crackable dalam hitungan detik lewat lookup table.</p>
  <a class="btn" href="lab2_unsalted_md5.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; "Enkripsi" yang sebenarnya cuma encoding</h3>
  <p>Cookie "remember me" menyimpan kredensial dengan base64 &mdash; terlihat acak, tapi reversible tanpa kunci apa pun.</p>
  <a class="btn" href="lab3_reversible_encoding.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
