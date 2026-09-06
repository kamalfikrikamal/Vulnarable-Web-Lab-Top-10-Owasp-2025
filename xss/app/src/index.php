<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab XSS (Cross-Site Scripting) sesuai kategori PortSwigger Web Security Academy:
reflected, stored, dan DOM-based, pada berbagai konteks output. Lihat <code>README.md</code>
untuk payload dan penjelasan tiap lab.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Reflected XSS (HTML body context)</h3>
  <p>Parameter pencarian ditampilkan kembali langsung ke dalam body HTML tanpa encoding.</p>
  <a class="btn" href="lab1_reflected.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Reflected XSS (HTML attribute context)</h3>
  <p>Input ditaruh di dalam atribut HTML (value="..."), butuh teknik keluar dari atribut.</p>
  <a class="btn" href="lab2_reflected_attribute.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Reflected XSS (JavaScript string context)</h3>
  <p>Input disisipkan ke dalam variabel JavaScript inline di dalam &lt;script&gt;.</p>
  <a class="btn" href="lab3_reflected_js.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Stored XSS (guestbook)</h3>
  <p>Komentar disimpan di server dan ditampilkan ke semua pengunjung tanpa sanitasi.</p>
  <a class="btn" href="lab4_stored_comments.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 5 &mdash; DOM-based XSS</h3>
  <p>JavaScript client-side membaca <code>location.hash</code> dan menulisnya ke innerHTML.</p>
  <a class="btn" href="lab5_dom_xss.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 6 &mdash; XSS dengan filter blacklist (bypass)</h3>
  <p>Ada filter naif yang memblok kata "script", tapi masih bisa dilewati.</p>
  <a class="btn" href="lab6_filter_bypass.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 7 &mdash; Reflected XSS via HTTP header (User-Agent)</h3>
  <p>Header User-Agent ditampilkan kembali di halaman "analytics" tanpa encoding.</p>
  <a class="btn" href="lab7_useragent.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
