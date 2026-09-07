<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kegagalan pembangkitan nilai acak (bagian dari Cryptographic Failures): token,
ID, dan kode yang seharusnya tidak bisa ditebak ternyata dibangkitkan dengan cara yang
deterministik atau berpola, sehingga penyerang bisa menebak/menghitung nilai milik orang lain.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Password reset token yang bisa diprediksi</h3>
  <p>Token reset password dibuat dari <code>md5(username . time())</code> &mdash; bisa dihitung ulang kalau waktu pembuatannya diketahui.</p>
  <a class="btn" href="lab1_predictable_reset_token.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; API key sekuensial</h3>
  <p>API key berupa angka urut biasa &mdash; tinggal naikkan satu angka untuk mengakses milik user lain.</p>
  <a class="btn" href="lab2_sequential_api_key.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; OTP 2FA yang bisa diprediksi</h3>
  <p>Kode OTP dibangkitkan dari seed yang deterministik (waktu server), bukan sumber acak yang aman secara kriptografis.</p>
  <a class="btn" href="lab3_predictable_otp.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Kode kupon yang bisa ditebak</h3>
  <p>Kode kupon diskon mengikuti pola sekuensial berdasarkan ID pesanan.</p>
  <a class="btn" href="lab4_predictable_coupon.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
