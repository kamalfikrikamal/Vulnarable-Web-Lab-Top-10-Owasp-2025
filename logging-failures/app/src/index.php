<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kegagalan logging &amp; alerting (<strong>A09: Logging &amp; Alerting Failures</strong>):
kejadian keamanan yang tidak tercatat dengan benar, log yang bisa dipalsukan penyerang, log yang
dianggap "data tepercaya" lalu dirender tanpa encoding, sampai data sensitif yang malah ikut
ditulis ke log &mdash; semua bikin tim keamanan buta terhadap serangan yang sebenarnya sedang
terjadi, atau malah membuka celah baru lewat log itu sendiri.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Log injection / log forgery</h3>
  <p>Username mentah ditulis apa adanya ke <code>auth.log</code> &mdash; newline yang disisipkan
  bisa memalsukan baris log baru yang tidak pernah benar-benar terjadi.</p>
  <a class="btn" href="lab1_log_injection_forgery.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Log injection berujung Stored XSS di dashboard admin</h3>
  <p>Query pencarian disimpan ke <code>search.log</code>, lalu ditampilkan mentah-mentah di
  dashboard admin &mdash; log dianggap "data internal tepercaya" padahal isinya tetap input user.</p>
  <a class="btn" href="lab2_log_injection_stored_xss.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Tidak ada logging/alerting untuk percobaan login gagal</h3>
  <p>Ratusan percobaan login gagal beruntun terhadap satu akun sama sekali tidak tercatat maupun
  memicu alert apa pun &mdash; celah deteksi yang serius.</p>
  <a class="btn" href="lab3_no_alerting_bruteforce.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Data sensitif ikut tercatat di log</h3>
  <p>Nomor kartu kredit dan CVV mentah ditulis ke <code>debug_requests.log</code> "buat
  debugging" &mdash; kalau log ini bocor, semua data itu ikut bocor.</p>
  <a class="btn" href="lab4_sensitive_data_in_logs.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
