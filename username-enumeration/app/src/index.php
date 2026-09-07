<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab username enumeration mengikuti kategori PortSwigger Web Security Academy
"Authentication": form login membocorkan informasi apakah sebuah username terdaftar atau tidak
lewat kanal yang tidak disadari developer — pesan error, panjang response, waktu respons, atau
perilaku lockout. Sekali username tervalidasi, serangan berikutnya (password spraying, brute
force terarah) jadi jauh lebih efisien.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Pesan error berbeda</h3>
  <p>"User tidak ditemukan" vs "Password salah" &mdash; dua pesan yang jelas berbeda.</p>
  <a class="btn" href="lab1_different_message.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Response nyaris identik tapi sedikit berbeda</h3>
  <p>Pesan generik yang sama persis secara visual, tapi berbeda satu karakter di response mentah.</p>
  <a class="btn" href="lab2_subtle_difference.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Perbedaan waktu respons</h3>
  <p>Username valid memicu pengecekan password yang lebih lambat daripada username tidak valid.</p>
  <a class="btn" href="lab3_response_timing.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Account lockout membocorkan validitas username</h3>
  <p>Pesan "akun terkunci" hanya muncul untuk username yang benar-benar terdaftar.</p>
  <a class="btn" href="lab4_account_lockout.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
