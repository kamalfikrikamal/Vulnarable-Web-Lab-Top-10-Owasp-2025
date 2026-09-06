<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab OS Command Injection mengikuti kategori PortSwigger Web Security Academy:
in-band (output terlihat), blind, dengan filter/blacklist yang bisa dilewati, dan argument
injection. Lihat <code>README.md</code> untuk payload dan penjelasan tiap lab.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Command Injection dengan output terlihat</h3>
  <p>Fitur "ping" menjalankan perintah shell dan menampilkan hasilnya langsung.</p>
  <a class="btn" href="lab1_visible.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Blind Command Injection (time-based)</h3>
  <p>Perintah dijalankan di background, respons selalu sama &mdash; gunakan delay sebagai sinyal.</p>
  <a class="btn" href="lab2_blind.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Command Injection dengan filter (bypass)</h3>
  <p>Karakter <code>; | &amp;</code> diblokir, tapi newline dan backtick tidak.</p>
  <a class="btn" href="lab3_filter_bypass.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Argument Injection</h3>
  <p>Input pengguna disisipkan sebagai argumen command-line ke <code>curl</code>, bukan lewat
  metakarakter shell &mdash; bisa disalahgunakan untuk menyisipkan flag berbahaya.</p>
  <a class="btn" href="lab4_argument_injection.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
