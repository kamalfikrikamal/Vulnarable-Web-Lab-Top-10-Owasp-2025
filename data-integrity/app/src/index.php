<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab <strong>Software or Data Integrity Failures</strong>: aplikasi yang mempercayai
data/kode/artifact tanpa memverifikasi keasliannya terlebih dahulu &mdash; mulai dari
mendeserialisasi objek PHP dari cookie milik attacker, menyimpan seluruh state klien di cookie
tanpa tanda tangan sama sekali, memverifikasi signature dengan cara yang tidak aman terhadap
timing attack, sampai menerima "update" sistem tanpa memeriksa checksum/signature-nya sama
sekali.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; PHP Object Injection lewat cookie</h3>
  <p>Cookie <code>remember_token</code> berisi objek PHP yang di-<code>serialize()</code>, lalu
  di-<code>unserialize()</code> lagi oleh server tanpa validasi apa pun. Ubah isinya untuk
  menjadi admin.</p>
  <a class="btn" href="lab1_php_object_injection.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; State cookie tanpa proteksi integritas</h3>
  <p>Saldo dan isi keranjang belanja disimpan seluruhnya di cookie <code>cart_state</code>
  (base64 dari JSON) &mdash; server percaya begitu saja apa pun yang dikirim balik, tidak ada
  tanda tangan yang mencegah perubahan nilai.</p>
  <a class="btn" href="lab2_unsigned_state_cookie.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Verifikasi signature pakai <code>==</code>, bukan <code>hash_equals()</code></h3>
  <p>Voucher diskon ditandatangani dengan HMAC-SHA256 asli, tapi perbandingan signature-nya
  memakai operator <code>==</code> yang tidak konstan waktu &mdash; rentan timing attack, dan
  kuncinya sendiri terlalu pendek untuk bertahan dari brute force.</p>
  <a class="btn" href="lab3_timing_unsafe_hmac.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Update sistem diterima tanpa verifikasi checksum</h3>
  <p>Halaman admin "Apply System Update" menerima file apa pun yang diunggah dan langsung
  menerapkannya sebagai update resmi &mdash; tidak ada perbandingan checksum/signature terhadap
  versi resmi sama sekali.</p>
  <a class="btn" href="lab4_update_no_checksum.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
