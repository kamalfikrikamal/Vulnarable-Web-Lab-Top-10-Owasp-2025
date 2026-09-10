<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kegagalan logging &amp; alerting (<strong>A09: Logging &amp; Alerting Failures</strong>):
kejadian keamanan yang tidak tercatat dengan benar, log yang bisa dipalsukan penyerang, log yang
dianggap "data tepercaya" lalu dirender tanpa encoding, sampai data sensitif yang malah ikut
ditulis ke log &mdash; semua bikin tim keamanan buta terhadap serangan yang sebenarnya sedang
terjadi, atau malah membuka celah baru lewat log itu sendiri. Grup lab kedua, <strong>Alerting
&amp; Log Integrity Gaps</strong>, melangkah lebih jauh: alerting yang "ada" tapi threshold-nya
bisa dihindari, log audit yang bisa dihapus oleh pemiliknya sendiri, kebocoran diam-diam lewat
console browser, dan log yang teknisnya "ada" tapi tidak cukup lengkap untuk benar-benar
diinvestigasi.</p>

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

<h2>Alerting &amp; Log Integrity Gaps</h2>
<p>Grup lab kedua: logging/alerting yang secara teknis "ada", tapi masih punya celah desain
serius &mdash; threshold yang bisa dihindari dengan pacing, log audit yang bisa dihapus oleh
akun yang justru diawasi, kebocoran data sensitif di channel yang tidak terlihat (console
browser), dan log yang kurang konteks untuk benar-benar dipakai investigasi.</p>

<div class="lab-card">
  <h3>Lab 5 &mdash; Alert threshold bisa dihindari dengan pacing serangan</h3>
  <p>Alert brute force baru terpicu kalau &gt;20 percobaan gagal masuk dalam 60 detik &mdash;
  penyerang yang mengirim batch di bawah angka itu, lalu menunggu, bisa mengumpulkan ratusan
  percobaan tanpa alert pernah terpicu satu kali pun.</p>
  <a class="btn" href="lab5_threshold_evasion.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 6 &mdash; User bisa menghapus log audit miliknya sendiri</h3>
  <p>Fitur "Hapus Riwayat Aktivitas Saya" yang terlihat seperti fitur privasi biasa ternyata
  langsung mengosongkan tabel <code>audit_log</code> yang sama yang dipakai tim SOC untuk
  investigasi keamanan.</p>
  <a class="btn" href="lab6_log_tampering.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 7 &mdash; Data sensitif bocor lewat console browser</h3>
  <p>Baris <code>console.log</code> debug yang lupa dihapus sebelum rilis menulis session token
  dan API key internal ke console setiap pengunjung &mdash; tidak terlihat di HTML atau network,
  tapi terbuka lebar di DevTools.</p>
  <a class="btn" href="lab7_client_side_console_logging.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 8 &mdash; Log ada, tapi tidak cukup konteks untuk investigasi</h3>
  <p>Log pembayaran mencatat jumlah transaksi, tapi tidak ada <code>user_id</code>,
  <code>ip</code>, <code>session_id</code>, atau <code>request_id</code> &mdash; investigasi
  insiden mentok walaupun "logging"-nya secara teknis berjalan.</p>
  <a class="btn" href="lab8_insufficient_log_context.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
