<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab Broken Function-Level Access Control (vertical privilege escalation) mengikuti
kategori PortSwigger Web Security Academy "Access control": aplikasi lupa memverifikasi bahwa
user yang mengakses sebuah fungsi memang punya hak (role) untuk itu. <a href="login.php">Login</a>
sebagai <code>alice</code> (user biasa), lalu coba naikkan hak akses lewat tiap lab di bawah
tanpa pernah tahu password admin.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Unprotected admin functionality</h3>
  <p>Halaman admin bisa diakses langsung asal sudah login, tanpa cek role sama sekali.</p>
  <a class="btn" href="lab1_unprotected_admin.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Unprotected admin dengan URL "tersembunyi"</h3>
  <p>URL admin tidak ditaruh di menu navigasi mana pun &mdash; tapi bocor lewat <code>robots.txt</code>.</p>
  <a class="btn" href="lab2_hidden_url.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Role ditentukan oleh cookie yang bisa diubah client</h3>
  <p>Panel admin membuka aksesnya berdasarkan nilai cookie <code>role</code>, bukan data user di server.</p>
  <a class="btn" href="lab3_role_cookie.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Method-based access control bypass</h3>
  <p>Cek role hanya berjalan untuk request GET; endpoint yang benar-benar menjalankan aksi (POST) tidak dicek.</p>
  <a class="btn" href="lab4_method_bypass.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 5 &mdash; Referer-based access control bypass</h3>
  <p>Akses admin "dianggap sah" kalau header <code>Referer</code> berasal dari menu admin &mdash; header ini sepenuhnya dikendalikan client.</p>
  <a class="btn" href="lab5_referer_bypass.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
