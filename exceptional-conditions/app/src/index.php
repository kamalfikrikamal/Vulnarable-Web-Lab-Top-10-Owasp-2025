<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab <strong>Mishandling of Exceptional Conditions</strong> (A10): kondisi tak terduga
&mdash; layanan eksternal timeout, input aneh, dua request yang datang nyaris bersamaan, atau
exception yang muncul di tempat yang tidak diduga &mdash; ditangani dengan cara yang justru
membuka celah keamanan, alih-alih ditolak/di-deny secara aman (fail closed). Grup kedua di bawah
(Lab 5&ndash;8) memperluas ke jenis kondisi tak terduga yang lain lagi: operasi yang diulang
(retry) padahal tidak idempotent, proses multi-langkah tanpa rollback, tipe data input yang tidak
sesuai dugaan filter, dan response API yang gagal di-parse tapi dianggap aman begitu saja.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Fail-open saat payment gateway timeout</h3>
  <p>Ketika layanan verifikasi pembayaran eksternal gagal/timeout, kode <code>catch</code>-nya
  malah menganggap pembayaran berhasil, bukan menahan/menolak order.</p>
  <a class="btn" href="lab1_fail_open_payment_timeout.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Pesan error bocorkan detail internal</h3>
  <p>Kalkulator diskon tidak menangani input di luar dugaan (nol, angka ekstrem, tipe salah)
  sehingga error bawaan PHP tampil apa adanya, lengkap dengan path file &amp; nomor baris.</p>
  <a class="btn" href="lab2_error_message_info_leak.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Race condition pada gift card</h3>
  <p>Pengecekan "sudah dipakai belum" dan penulisan status "sudah dipakai" dilakukan sebagai dua
  langkah terpisah dengan jeda di antaranya &mdash; dua request nyaris bersamaan bisa lolos
  keduanya dan me-redeem gift card yang sama lebih dari sekali.</p>
  <a class="btn" href="lab3_race_condition_giftcard.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Fail-open di dalam catch block pengecekan akses</h3>
  <p>Pengecekan kepemilikan laporan dibungkus <code>try/catch(\Throwable)</code> yang, kalau
  terjadi error tak terduga saat pengecekan, malah meloloskan akses alih-alih menolaknya.</p>
  <a class="btn" href="lab4_failopen_catch_block.php">Open Lab</a>
</div>

<h2>Non-Atomic &amp; Type-Unsafe Exceptional Handling</h2>
<p>Grup ini fokus ke kondisi tak terduga yang muncul bukan dari layanan eksternal yang mati total,
tapi dari asumsi keliru soal atomicity (operasi multi-langkah yang seharusnya "semua-atau-tidak
sama sekali") dan soal tipe data (asumsi bahwa input selalu berbentuk seperti yang diharapkan).</p>

<div class="lab-card">
  <h3>Lab 5 &mdash; Retry non-idempotent setelah timeout menyebabkan double charge</h3>
  <p>Klik "Coba Lagi" setelah simulasi response timeout mengirim ulang permintaan bayar yang
  PERSIS SAMA tanpa idempotency key &mdash; padahal charge yang "timeout" itu sebenarnya sudah
  diproses sukses di backend. Hasilnya: dua charge nyata untuk satu order yang sama.</p>
  <a class="btn" href="lab5_duplicate_charge_retry.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 6 &mdash; Transaksi multi-langkah tanpa rollback</h3>
  <p>Transfer dana dilakukan sebagai dua langkah terpisah (potong saldo akun A, lalu kredit akun
  B) tanpa transaksi/rollback pembungkus. Kalau langkah kedua gagal, saldo akun A tetap terlanjur
  terpotong &mdash; uang lenyap dari sistem, murni dari satu request biasa (bukan race condition).</p>
  <a class="btn" href="lab6_no_rollback_partial_failure.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 7 &mdash; Tipe input tak terduga meloloskan filter <code>in_array()</code></h3>
  <p>Filter nama tampilan memakai <code>in_array($input, $BLOCKED_VALUES)</code> yang berasumsi
  input selalu berupa string. Kirim field sebagai array (<code>display_name[]=admin</code>)
  alih-alih string biasa, dan nilai yang sama persis lolos begitu saja dari blocklist.</p>
  <a class="btn" href="lab7_type_confusion_filter_bypass.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 8 &mdash; Response fraud-check gagal di-parse dianggap aman</h3>
  <p>Saat response API fraud-check berbentuk tak terduga (bukan timeout, tapi shape JSON yang
  beda), kode pemanggil pakai <code>$fraud_result['flagged'] ?? false</code> &mdash; default
  <code>??</code> ini diam-diam menganggap order aman, padahal fraud check-nya tidak pernah benar-benar
  jalan.</p>
  <a class="btn" href="lab8_malformed_response_fail_open.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
