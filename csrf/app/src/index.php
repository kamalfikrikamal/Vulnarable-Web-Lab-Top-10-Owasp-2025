<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab Cross-Site Request Forgery (CSRF) mengikuti kategori PortSwigger Web Security
Academy "Cross-site request forgery (CSRF)": aplikasi menjalankan aksi yang mengubah state
(ubah email, dsb.) berdasarkan request yang datang dengan cookie session valid, tanpa cukup
memverifikasi bahwa request itu memang sengaja dikirim oleh pengguna dari halaman aplikasi
sendiri &mdash; bukan dipicu diam-diam oleh halaman pihak ketiga. <a href="login.php">Login</a>
dulu sebagai <code>victim</code>, lalu buka PoC HTML tiap lab di tab/browser terpisah untuk
mensimulasikan halaman attacker.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Tidak ada token CSRF sama sekali</h3>
  <p>Form ubah email tidak punya perlindungan CSRF apa pun.</p>
  <a class="btn" href="lab1_no_token.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Token CSRF tidak diikat ke session</h3>
  <p>Ada token, tapi validasinya cuma "apakah token ini pernah diterbitkan", bukan "apakah token ini milik session yang sedang mengirim request".</p>
  <a class="btn" href="lab2_token_not_tied.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Validasi token bisa dilewati dengan menghapus parameternya</h3>
  <p>Validasi token cuma jalan KALAU parameter token dikirim.</p>
  <a class="btn" href="lab3_token_removal.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Aksi sensitif lewat GET request</h3>
  <p>Ubah email bisa dipicu lewat request GET &mdash; bisa dieksploitasi hanya dengan sebuah tag <code>&lt;img&gt;</code>.</p>
  <a class="btn" href="lab4_get_based.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
