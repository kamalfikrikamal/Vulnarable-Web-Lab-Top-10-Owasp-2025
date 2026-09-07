<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kerentanan JSON Web Token (JWT), bagian dari Cryptographic Failures: aplikasi
mempercayai isi token tanpa benar-benar memverifikasi tanda tangannya dengan aman. Setiap lab
menerbitkan token "login sebagai user biasa" untukmu, lalu tugasmu adalah memalsukan token yang
diakui server sebagai <strong>admin</strong>.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Algoritma <code>none</code> diterima</h3>
  <p>Server tidak menolak token yang mengaku memakai algoritma "tanpa tanda tangan".</p>
  <a class="btn" href="lab1_alg_none.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Secret HMAC lemah</h3>
  <p>Token ditandatangani dengan secret pendek yang ada di wordlist umum.</p>
  <a class="btn" href="lab2_weak_secret.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Signature tidak pernah diverifikasi</h3>
  <p>Server mempercayai isi payload begitu saja tanpa mengecek tanda tangan sama sekali.</p>
  <a class="btn" href="lab3_no_signature_check.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Path traversal lewat header <code>kid</code></h3>
  <p>Header <code>kid</code> dipakai untuk membaca file kunci dari disk tanpa sanitasi path.</p>
  <a class="btn" href="lab4_kid_path_traversal.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
