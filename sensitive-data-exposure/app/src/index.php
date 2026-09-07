<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kebocoran data sensitif (bagian dari Cryptographic Failures): data yang
seharusnya dilindungi ternyata bocor lewat kanal yang tidak diduga &mdash; URL yang tercatat di
banyak tempat, cache yang tidak dikontrol, atau response yang menampilkan lebih banyak data
daripada yang seharusnya.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Data sensitif di URL (bocor lewat Referer)</h3>
  <p>Nomor kartu kredit dikirim lewat query string, ikut bocor ke pihak ketiga lewat header Referer.</p>
  <a class="btn" href="lab1_url_leak.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Header Cache-Control tidak diset</h3>
  <p>Halaman berisi data akun sensitif tanpa <code>Cache-Control: no-store</code> &mdash; bisa tersimpan di shared/proxy cache dan terlihat oleh user lain.</p>
  <a class="btn" href="lab2_missing_cache_control.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Data sensitif tidak di-mask</h3>
  <p>Fungsi masking sudah ada di kodebase, tapi tidak dipakai di satu endpoint tertentu.</p>
  <a class="btn" href="lab3_unmasked_response.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
