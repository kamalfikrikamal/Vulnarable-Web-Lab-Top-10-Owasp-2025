<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kerentanan <strong>Insecure Design</strong> (A06): alur bisnis (checkout,
kupon, referral, multi-step workflow, login bertahap, return/refund) yang secara logika
"berfungsi normal", tapi tidak pernah benar-benar dirancang untuk menahan penyalahgunaan. Bug-nya
bukan di syntax atau library yang salah pakai, tapi di asumsi bisnis yang tidak pernah
divalidasi/dipaksakan di sisi server.</p>

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

<h2>Flawed Multi-Step Authentication Logic</h2>
<p>Alur login bertahap (password &rarr; 2FA) yang komponennya masing-masing terlihat benar, tapi
urutan/penegakannya di server punya celah.</p>

<div class="lab-card">
  <h3>Lab 6 &mdash; 2FA bypass lewat forced browsing</h3>
  <p>Halaman dashboard cuma mengecek flag "password benar", bukan flag "OTP terverifikasi" &mdash; langsung mengetik URL step berikutnya melewati verifikasi OTP sama sekali.</p>
  <a class="btn" href="lab6_2fa_forced_browsing.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 7 &mdash; Ganti password tanpa re-autentikasi</h3>
  <p>Form ganti password tidak pernah meminta password lama &mdash; sesi yang dicuri/dipinjam sebentar cukup untuk mengunci pemilik akun asli secara permanen.</p>
  <a class="btn" href="lab7_password_change_no_reauth.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 8 &mdash; Trusted device bypass 2FA</h3>
  <p>Cookie "perangkat terpercaya" isinya cuma username polos, tanpa token acak/binding apa pun &mdash; siapa pun yang menebak/menyalin nilainya melewati OTP di device manapun.</p>
  <a class="btn" href="lab8_trusted_device_bypass.php">Open Lab</a>
</div>

<h2>Business Rule Enforcement Gaps</h2>
<p>Aturan bisnis yang seharusnya jelas ("satu kupon sekali pakai", "harga sesuai region asli",
"refund tidak boleh melebihi pembelian") ternyata tidak pernah benar-benar ditegakkan di server.</p>

<div class="lab-card">
  <h3>Lab 9 &mdash; HTTP Parameter Pollution pada kupon</h3>
  <p>Field kupon yang sah mendukung banyak kode sekaligus (array) tidak pernah men-deduplikasi kode yang sama muncul lebih dari sekali &mdash; diskon diterapkan berkali-kali dalam satu request.</p>
  <a class="btn" href="lab9_coupon_parameter_pollution.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 10 &mdash; Price spoofing lewat header region</h3>
  <p>Harga produk digital ditentukan dari header <code>X-Region</code> yang sepenuhnya dikendalikan client, bukan dari sumber tepercaya seperti IP atau alamat billing terverifikasi.</p>
  <a class="btn" href="lab10_region_price_spoofing.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 11 &mdash; Over-refund lewat kuantitas return</h3>
  <p>Kuantitas return tidak pernah dibandingkan dengan kuantitas yang benar-benar dibeli di order tersebut &mdash; refund bisa jauh melampaui total pembelian asli.</p>
  <a class="btn" href="lab11_over_refund.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
