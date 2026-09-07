<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kegagalan alur reset password, mengikuti kategori PortSwigger Web Security
Academy "Authentication" — bagian dari A07: Authentication Failures. Target semua lab: akun
<code>admin</code>.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Token reset bocor lewat tracking pixel email</h3>
  <p>Email reset memuat gambar pelacak yang membawa token lengkap di URL-nya.</p>
  <a class="btn" href="lab1_token_leak_email.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Token reset bisa dipakai berulang kali</h3>
  <p>Token tidak pernah ditandai "sudah dipakai" setelah reset berhasil.</p>
  <a class="btn" href="lab2_token_reuse.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Kode reset pendek tanpa rate limiting</h3>
  <p>Kode verifikasi cuma 4 digit dan bisa dicoba tanpa batas.</p>
  <a class="btn" href="lab3_brute_forceable_code.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Password reset poisoning lewat Host header</h3>
  <p>Link reset dibangun dari header <code>Host</code> yang dikendalikan pengirim request.</p>
  <a class="btn" href="lab4_host_header_poisoning.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
