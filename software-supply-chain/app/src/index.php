<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab <strong>Software Supply Chain Failures</strong> (OWASP A03:2025): kerentanan yang
tidak muncul dari kode yang kamu tulis sendiri, tapi dari mata rantai di sekitarnya &mdash;
library pihak ketiga, nama paket internal, pipeline CI/CD, mekanisme auto-update, dan dependency
yang tidak pernah direview. Kalau salah satu mata rantai ini disusupi, aplikasi ikut terdampak
walau source code utamanya aman.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Prototype Pollution di library bundel (client-side)</h3>
  <p>Fungsi "merge/extend" versi lama (mensimulasikan lodash <code>merge()</code> sebelum 4.17.5,
  CVE-2018-3721) tidak memblokir key <code>__proto__</code> &mdash; payload JSON bisa meracuni
  <code>Object.prototype</code> untuk semua object di halaman.</p>
  <a class="btn" href="lab1_prototype_pollution.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Dependency Confusion</h3>
  <p>Nama paket internal bocor lewat file konfigurasi yang ter-deploy ke webroot. Publikasikan
  paket publik dengan nama yang sama, dan build internal berikutnya menjalankan kodemu.</p>
  <a class="btn" href="lab2_dependency_confusion.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; CI/CD Config Bocor Berisi Secret</h3>
  <p>File workflow CI/CD ikut ter-deploy ke webroot secara tidak sengaja, lengkap dengan token
  deploy production yang di-hardcode di dalamnya.</p>
  <a class="btn" href="lab3_cicd_secret_exposure.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Auto-Update Tanpa Verifikasi Signature</h3>
  <p>Fitur auto-update plugin menerima dan "menerapkan" paket apa pun dari URL yang diberikan
  tanpa pernah memverifikasi signature/checksum-nya.</p>
  <a class="btn" href="lab4_unsigned_autoupdate.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 5 &mdash; Postinstall Script Jahat dari Dependency yang Tidak Direview</h3>
  <p>Paket pihak ketiga bisa mendefinisikan skrip lifecycle (<code>postinstall</code>) yang
  otomatis dieksekusi penuh saat proses build &mdash; tanpa review, ini jadi RCE gratis.</p>
  <a class="btn" href="lab5_malicious_postinstall.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
