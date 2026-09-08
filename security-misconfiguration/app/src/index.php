<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab <strong>Security Misconfiguration (A02)</strong>: aplikasi, server, atau
komponennya dikonfigurasi secara tidak aman &mdash; mode debug aktif di production, kredensial
default tidak diganti, directory listing terbuka, endpoint debug lupa dihapus, CORS mengizinkan
origin apa saja dengan credentials, hingga header keamanan yang tidak pernah dipasang. Semua ini
bukan bug di kode aplikasi, melainkan kesalahan konfigurasi yang membuka celah besar.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Debug mode / stack trace bocor ke production</h3>
  <p>Mode debug (<code>APP_DEBUG=true</code>) masih aktif di production &mdash; error yang seharusnya generik malah menampilkan query SQL, path server, bahkan kredensial database mentah-mentah.</p>
  <a class="btn" href="lab1_debug_stacktrace.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Kredensial default tidak pernah diganti</h3>
  <p>Panel admin masih memakai kredensial bawaan vendor (<code>admin</code> / <code>admin123</code>) yang tidak pernah diganti sebelum go-live.</p>
  <a class="btn" href="lab2_default_credentials.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Directory listing terbuka</h3>
  <p>Folder backup di webroot punya <code>Options +Indexes</code> aktif &mdash; isinya (dump database, config lama) bisa dijelajahi langsung dari browser.</p>
  <a class="btn" href="lab3_directory_listing.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Endpoint debug yang terlupakan</h3>
  <p>Halaman debug internal peninggalan development, tidak pernah dihapus atau dilindungi, dan tidak tertaut dari menu mana pun &mdash; tapi tetap bisa diakses siapa saja yang tahu (atau menebak) URL-nya.</p>
  <a class="btn" href="lab4_debug_endpoint.php">Mulai Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 5 &mdash; CORS misconfiguration</h3>
  <p>API internal mengembalikan <code>Access-Control-Allow-Origin</code> yang me-reflect origin apa pun, dikombinasikan dengan <code>Access-Control-Allow-Credentials: true</code> &mdash; situs mana pun bisa membaca data milik user yang sedang login.</p>
  <a class="btn" href="lab5_cors_misconfig.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 6 &mdash; Header keamanan hilang &rarr; Clickjacking</h3>
  <p>Halaman transfer dana tidak mengirim <code>X-Frame-Options</code> maupun CSP <code>frame-ancestors</code>, sehingga bisa di-embed di iframe tersembunyi milik attacker dan dipicu tanpa sepengetahuan korban.</p>
  <a class="btn" href="lab6_missing_headers_clickjacking.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
