<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab <strong>Software or Data Integrity Failures</strong>: aplikasi yang mempercayai
data/kode/artifact tanpa memverifikasi keasliannya terlebih dahulu &mdash; mulai dari
mendeserialisasi objek PHP dari cookie milik attacker, menyimpan seluruh state klien di cookie
tanpa tanda tangan sama sekali, memverifikasi signature dengan cara yang tidak aman terhadap
timing attack maupun type juggling, secret signing yang bocor, signature yang tidak menutupi
seluruh data, checksum yang datang dari sumber tidak tepercaya, sampai input eksternal yang
dipercaya untuk membentuk state internal program (variabel maupun kode yang dijalankan) itu
sendiri.</p>

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

<h2>Broken Integrity Verification Mechanisms</h2>
<p>Mekanisme verifikasi integritasnya ADA, tapi masing-masing rusak dengan cara yang berbeda
&mdash; dari kelemahan tipe data, secret yang bocor, sampai signature yang tidak menutupi seluruh
data yang seharusnya dilindungi.</p>

<div class="lab-card">
  <h3>Lab 5 &mdash; Magic Hash / Type Juggling Bypass</h3>
  <p>Perbandingan hash pakai <code>==</code> membuat string "0e" + digit ditafsirkan sebagai notasi ilmiah &mdash; dua hash yang sama sekali berbeda isinya bisa dianggap "sama" tanpa pernah tahu kode aslinya.</p>
  <a class="btn" href="lab5_magic_hash_bypass.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 6 &mdash; Signing Secret Bocor di Client-Side JS</h3>
  <p>Secret HMAC yang dipakai menandatangani link reset password juga tertanam di file JavaScript publik untuk fitur "live preview" &mdash; siapa pun bisa memalsukan token untuk email siapa pun.</p>
  <a class="btn" href="lab6_leaked_signing_secret.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 7 &mdash; Signature Cuma Menutupi Sebagian Data</h3>
  <p>Signature transfer dana cuma menandatangani field <code>amount</code> &mdash; recipient/currency bisa diubah bebas tanpa membuat signature-nya tidak valid.</p>
  <a class="btn" href="lab7_partial_signature_gap.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 8 &mdash; Checksum dari Sumber yang Sama dengan Artifact</h3>
  <p>Checksum "resmi" untuk verifikasi package datang dari form/party yang sama dengan package itu sendiri &mdash; verifikasinya benar secara matematis tapi tidak membuktikan apa pun.</p>
  <a class="btn" href="lab8_checksum_same_source.php">Open Lab</a>
</div>

<h2>Untrusted Input Shaping Program State</h2>
<p>Input dari luar dipercaya untuk menentukan bentuk/state internal program itu sendiri &mdash;
nama variabel, bahkan kode apa yang dijalankan &mdash; bukan cuma nilai data biasa.</p>

<div class="lab-card">
  <h3>Lab 9 &mdash; Variable Injection Lewat extract()</h3>
  <p><code>extract($_GET)</code> menimpa variabel internal (<code>$is_admin</code>, <code>$account_balance</code>) yang seharusnya cuma bisa diisi dari sumber tepercaya di server.</p>
  <a class="btn" href="lab9_extract_variable_injection.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 10 &mdash; Dynamic Dispatch dari Input Tak Tepercaya</h3>
  <p>Dispatcher memanggil fungsi apa pun yang namanya cocok dengan parameter <code>action</code> &mdash; termasuk fungsi internal yang tidak pernah dimaksudkan bisa diakses lewat HTTP sama sekali.</p>
  <a class="btn" href="lab10_untrusted_dynamic_dispatch.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
