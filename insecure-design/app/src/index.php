<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kerentanan <strong>Insecure Design</strong> (A06): alur bisnis (checkout,
kupon, referral, multi-step workflow) yang secara logika "berfungsi normal", tapi tidak pernah
benar-benar dirancang untuk menahan penyalahgunaan. Bug-nya bukan di syntax atau library yang
salah pakai, tapi di asumsi bisnis yang tidak pernah divalidasi/dipaksakan di sisi server.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Price tampering lewat hidden field</h3>
  <p>Harga produk dikirim sebagai <code>&lt;input type="hidden" name="price"&gt;</code> dan
  dipercaya mentah-mentah oleh server saat menghitung total belanja.</p>
  <a class="btn" href="lab1_price_tampering.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Kuantitas negatif</h3>
  <p>Field <code>quantity</code> tidak pernah divalidasi harus positif, sehingga kuantitas
  negatif bisa "membalik" total belanja menjadi saldo refund.</p>
  <a class="btn" href="lab2_negative_quantity.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Kupon diskon bisa ditumpuk (stacking)</h3>
  <p>Kupon <code>DISKON20</code> yang seharusnya sekali pakai tidak pernah ditandai "sudah
  dipakai", jadi bisa direplay berkali-kali untuk diskon berlapis.</p>
  <a class="btn" href="lab3_coupon_stacking.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Lompat step pada checkout bertahap</h3>
  <p>Checkout 3 langkah (cart &rarr; pembayaran &rarr; selesai) hanya "dipandu" lewat tautan UI
  &mdash; step konfirmasi tidak pernah mengecek apakah step pembayaran benar-benar terjadi.</p>
  <a class="btn" href="lab4_skip_checkout_step.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 5 &mdash; Bonus referral tanpa batas</h3>
  <p>Program referral hanya mengecek keunikan <em>username</em> secara string &mdash; tidak ada
  verifikasi email, batas per-IP, atau deteksi fraud, jadi bisa di-farming tanpa batas.</p>
  <a class="btn" href="lab5_unlimited_referral_abuse.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
