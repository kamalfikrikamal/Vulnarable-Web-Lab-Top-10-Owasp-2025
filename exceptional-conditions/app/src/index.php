<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab <strong>Mishandling of Exceptional Conditions</strong> (A10): kondisi tak terduga
&mdash; layanan eksternal timeout, input aneh, dua request yang datang nyaris bersamaan, atau
exception yang muncul di tempat yang tidak diduga &mdash; ditangani dengan cara yang justru
membuka celah keamanan, alih-alih ditolak/di-deny secara aman (fail closed).</p>

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

<?php include 'footer.php'; ?>
