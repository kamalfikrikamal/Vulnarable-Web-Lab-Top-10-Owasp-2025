<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab Insecure Direct Object Reference (IDOR) mengikuti kategori PortSwigger Web
Security Academy "Access control": server mempercayai ID/identifier yang dikirim client untuk
menentukan data mana yang diambil/diubah, tanpa memverifikasi bahwa data itu memang milik user
yang sedang login. <a href="login.php">Login</a> dulu sebagai salah satu akun demo, lalu coba
tiap lab di bawah.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Basic IDOR (baca data user lain)</h3>
  <p>Halaman "invoice saya" mengambil data lewat parameter <code>id</code> tanpa mengecek kepemilikan.</p>
  <a class="btn" href="lab1_basic_idor.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; IDOR pada aksi tulis (update/hapus)</h3>
  <p>Bukan cuma baca &mdash; parameter ID yang sama dipakai untuk mengubah data user lain.</p>
  <a class="btn" href="lab2_idor_write.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; IDOR dengan ID "acak" yang bocor</h3>
  <p>ID diganti jadi token acak supaya "susah ditebak" &mdash; tapi tetap bocor lewat endpoint lain.</p>
  <a class="btn" href="lab3_idor_unpredictable.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; IDOR di endpoint JSON/API</h3>
  <p>Endpoint API mengembalikan seluruh objek user, termasuk field sensitif yang tidak seharusnya ditampilkan ke sesama user biasa.</p>
  <a class="btn" href="lab4_idor_api.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 5 &mdash; Mass Assignment</h3>
  <p>Form update profil menerima field ekstra yang tidak dimaksudkan untuk bisa diubah user biasa (mis. <code>role</code>).</p>
  <a class="btn" href="lab5_mass_assignment.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
