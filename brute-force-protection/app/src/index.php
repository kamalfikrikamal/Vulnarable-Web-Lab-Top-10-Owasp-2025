<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kegagalan proteksi brute-force, mengikuti kategori PortSwigger Web Security
Academy "Authentication" — bagian dari A07: Authentication Failures. Target di semua lab: akun
<code>admin</code> dengan password yang tidak diketahui.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Tidak ada rate limiting sama sekali</h3>
  <p>Login bisa dicoba tanpa batas, secepat apa pun, tanpa CAPTCHA maupun lockout.</p>
  <a class="btn" href="lab1_no_rate_limit.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Lockout berbasis IP, bypass lewat X-Forwarded-For</h3>
  <p>Server mempercayai header <code>X-Forwarded-For</code> untuk menentukan IP attacker.</p>
  <a class="btn" href="lab2_xff_bypass.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Lockout bisa direset dengan variasi kapitalisasi username</h3>
  <p>Counter percobaan gagal case-sensitive, padahal proses login sendiri case-insensitive.</p>
  <a class="btn" href="lab3_case_variation_bypass.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
