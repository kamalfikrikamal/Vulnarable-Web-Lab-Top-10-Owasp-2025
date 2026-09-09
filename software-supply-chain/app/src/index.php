<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab <strong>Software Supply Chain Failures</strong> (OWASP A03:2025): kerentanan yang
tidak muncul dari kode yang kamu tulis sendiri, tapi dari mata rantai di sekitarnya &mdash;
library pihak ketiga, nama paket internal, pipeline CI/CD, mekanisme auto-update, dependency yang
tidak pernah direview, paket lookalike, skrip CDN tanpa verifikasi, lockfile yang tidak
ditegakkan, hingga referensi mutable (tag) yang dipakai alih-alih referensi immutable (SHA/digest)
untuk Action CI/CD maupun base image container. Kalau salah satu mata rantai ini disusupi,
aplikasi ikut terdampak walau source code utamanya aman.</p>

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

<h2>Malicious / Compromised Package Content</h2>
<p>Konten dependency yang benar-benar terpasang ternyata berbeda dari yang dimaksudkan/direview
tim &mdash; karena namanya disamarkan, sumbernya tidak diverifikasi, atau lockfile-nya tidak
ditegakkan.</p>

<div class="lab-card">
  <h3>Lab 6 &mdash; Typosquatting</h3>
  <p>Paket dengan nama nyaris identik dengan paket resmi (satu-dua karakter berbeda) didaftarkan attacker di registry publik &mdash; korban ter-install paket yang salah karena copy-paste command tanpa mengecek ulang.</p>
  <a class="btn" href="lab6_typosquatting.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 7 &mdash; Missing Subresource Integrity (SRI)</h3>
  <p>Skrip pihak ketiga dimuat dari CDN tanpa atribut <code>integrity</code> &mdash; kalau CDN-nya disusupi, skrip yang sudah dimodifikasi tetap dijalankan browser tanpa verifikasi apa pun.</p>
  <a class="btn" href="lab7_missing_sri.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 8 &mdash; Lockfile Diabaikan Saat Build</h3>
  <p>Lockfile mengunci versi dependency yang sudah direview, tapi proses build tidak menegakkannya &mdash; versi lebih baru yang belum direview (dan sudah disusupi) diam-diam terpasang.</p>
  <a class="btn" href="lab8_lockfile_ignored.php">Open Lab</a>
</div>

<h2>Unpinned / Mutable Build References</h2>
<p>Referensi mutable (tag) dipakai alih-alih referensi immutable (SHA/digest) &mdash; siapa pun
yang mengendalikan sumber upstream bisa mengganti apa yang ditunjuk referensi itu kapan saja,
tanpa developer yang memakainya sadar sama sekali.</p>

<div class="lab-card">
  <h3>Lab 9 &mdash; CI Action Dipin ke Tag Mutable</h3>
  <p>Workflow memanggil Action pihak ketiga lewat tag (<code>@v1</code>) yang bisa dipindah maintainer/attacker kapan saja &mdash; dibandingkan dengan pin ke commit SHA yang isinya tidak pernah berubah.</p>
  <a class="btn" href="lab9_ci_action_mutable_tag.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 10 &mdash; Container Base Image Dipin ke Tag Mutable</h3>
  <p>Dockerfile memakai <code>:latest</code> alih-alih digest <code>sha256:...</code> &mdash; image yang ter-build bisa diam-diam berubah kalau registry-nya disusupi.</p>
  <a class="btn" href="lab10_mutable_base_image.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
