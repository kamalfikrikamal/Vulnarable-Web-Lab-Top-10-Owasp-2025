<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab <strong>Security Misconfiguration (A02)</strong>: aplikasi, server, atau
komponennya dikonfigurasi secara tidak aman &mdash; mode debug aktif di production, kredensial
default tidak diganti, directory listing terbuka, endpoint debug lupa dihapus, CORS mengizinkan
origin apa saja dengan credentials, header keamanan yang tidak pernah dipasang, file/folder
sensitif (<code>.git</code>, <code>.env</code>, backup editor) ikut ter-deploy ke webroot, atribut
keamanan cookie yang dilewatkan, hingga HTTP method yang seharusnya dimatikan tapi tetap aktif.
Semua ini bukan bug di kode aplikasi, melainkan kesalahan konfigurasi yang membuka celah besar.</p>

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

<h2>Exposed VCS / Config Files</h2>
<p>File dan folder yang seharusnya tidak pernah ikut ter-deploy ke webroot production, tapi
tetap bisa diakses karena tidak ada aturan eksplisit yang memblokirnya.</p>

<div class="lab-card">
  <h3>Lab 7 &mdash; Folder .git ter-expose</h3>
  <p>Subsite kecil di-deploy dengan menyalin seluruh folder kerja apa adanya, termasuk <code>.git</code> &mdash; seluruh riwayat commit, termasuk secret yang "pernah dihapus", bisa direkonstruksi dari sana.</p>
  <a class="btn" href="lab7_git_exposed.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 8 &mdash; File .env ter-expose</h3>
  <p>File konfigurasi <code>.env</code> berisi kredensial database &amp; application key ikut ter-deploy di root aplikasi dan bisa diakses langsung lewat URL.</p>
  <a class="btn" href="lab8_env_exposed.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 9 &mdash; Backup file editor yang bisa ditebak</h3>
  <p>File recovery text editor (<code>config.php.save</code>) ketinggalan di webroot &mdash; berisi source code &amp; kredensial mentah, dikirim sebagai teks biasa karena ekstensinya bukan <code>.php</code>.</p>
  <a class="btn" href="lab9_backup_file_guess.php">Open Lab</a>
</div>

<h2>Cookie Security Misconfiguration</h2>
<p>Atribut keamanan cookie (<code>HttpOnly</code>, <code>Secure</code>, <code>SameSite</code>)
yang seharusnya selalu dipasang di cookie sesi/autentikasi, tapi dilewatkan.</p>

<div class="lab-card">
  <h3>Lab 10 &mdash; Cookie tanpa flag HttpOnly</h3>
  <p>Cookie sesi bisa dibaca lewat <code>document.cookie</code> &mdash; dibuktikan lewat bug XSS reflected yang membocorkan tokennya langsung.</p>
  <a class="btn" href="lab10_missing_httponly.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 11 &mdash; Cookie tanpa flag Secure</h3>
  <p>Cookie sesi tetap terkirim ke endpoint plain HTTP mana pun &mdash; tidak ada jaminan browser bahwa cookie ini hanya melintas lewat koneksi terenkripsi.</p>
  <a class="btn" href="lab11_missing_secure.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 12 &mdash; Cookie tanpa atribut SameSite</h3>
  <p>Atribut <code>SameSite</code> tidak dideklarasikan sama sekali &mdash; browser/klien lama memperlakukannya seolah <code>None</code>, dikirim di semua request lintas situs.</p>
  <a class="btn" href="lab12_missing_samesite.php">Open Lab</a>
</div>

<h2>Insecure HTTP Methods</h2>
<p>HTTP method di luar <code>GET</code>/<code>POST</code> yang seharusnya dimatikan di
production, tapi tetap diterima server/aplikasi.</p>

<div class="lab-card">
  <h3>Lab 13 &mdash; HTTP method TRACE aktif (XST)</h3>
  <p>Server meng-echo balik seluruh header request apa adanya lewat method <code>TRACE</code> &mdash; termasuk header <code>Cookie</code>, celah historis untuk melewati proteksi HttpOnly.</p>
  <a class="btn" href="lab13_trace_method.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 14 &mdash; HTTP method PUT diterima tanpa validasi</h3>
  <p>Request <code>PUT</code> langsung menyimpan file apa pun ke direktori yang dieksekusi PHP &mdash; webshell bisa ditanam tanpa pernah menyentuh form upload aplikasi.</p>
  <a class="btn" href="lab14_put_method.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
