<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab Local File Inclusion (LFI) / Path Traversal mengikuti kategori PortSwigger Web
Security Academy (<em>File path traversal</em>) ditambah teknik lanjutan LFI-to-RCE lewat PHP
stream wrapper dan log poisoning. Lihat <code>README.md</code> untuk payload dan penjelasan
tiap lab.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Basic LFI (simple case)</h3>
  <p>Parameter <code>page</code> langsung dimasukkan ke <code>include()</code> tanpa validasi
  sama sekali.</p>
  <a class="btn" href="lab1_basic.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Traversal diblokir, tapi absolute path lolos</h3>
  <p>Filter hanya mencari substring <code>../</code>, sehingga path absolut yang tidak
  membutuhkan traversal sama sekali tetap lolos.</p>
  <a class="btn" href="lab2_absolute_bypass.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Traversal sequence dihapus non-recursive</h3>
  <p><code>../</code> dihapus hanya satu kali, bukan berulang sampai bersih.</p>
  <a class="btn" href="lab3_nonrecursive_strip.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Filter lalu URL-decode berlebih</h3>
  <p>Aplikasi men-decode input sekali lagi <strong>setelah</strong> filter traversal
  dijalankan.</p>
  <a class="btn" href="lab4_double_decode.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 5 &mdash; Validasi hanya di awal path</h3>
  <p>Aplikasi hanya memastikan path <em>dimulai</em> dengan folder yang diizinkan, tanpa
  menormalisasi sisa path-nya.</p>
  <a class="btn" href="lab5_start_validation.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 6 &mdash; Validasi ekstensi dengan null byte bypass</h3>
  <p>Validasi ekstensi file bisa dilewati dengan menyisipkan null byte (disimulasikan untuk
  mempelajari teknik historis ini).</p>
  <a class="btn" href="lab6_extension_nullbyte.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 7 &mdash; LFI to RCE (PHP wrappers &amp; log poisoning)</h3>
  <p>Tidak ada validasi sama sekali &mdash; naikkan level dari sekadar membaca file menjadi
  eksekusi kode lewat <code>php://filter</code>, <code>php://input</code>, dan log
  poisoning.</p>
  <a class="btn" href="lab7_wrappers_rce.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
