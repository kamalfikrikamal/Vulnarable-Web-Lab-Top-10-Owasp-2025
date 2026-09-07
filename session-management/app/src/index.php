<?php $title = 'Home'; include 'header.php'; ?>

<p>Kumpulan lab kegagalan manajemen sesi, bagian dari A07: Authentication Failures. Tiap lab
memakai mekanisme token (<code>authtoken</code>) buatan sendiri (bukan session PHP bawaan)
supaya setiap kesalahan penanganan token bisa didemonstrasikan secara terpisah dan jelas. Akun
demo: <code>alice/alice123</code>.</p>

<div class="lab-card">
  <h3>Lab 1 &mdash; Token tetap valid setelah logout</h3>
  <p>Logout cuma menghapus cookie di browser, token-nya sendiri tidak pernah di-invalidate di server.</p>
  <a class="btn" href="lab1_token_survives_logout.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 2 &mdash; Session ID bisa diprediksi</h3>
  <p>Token berupa angka urut (sequential), bukan nilai acak.</p>
  <a class="btn" href="lab2_predictable_session_id.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 3 &mdash; Session Fixation</h3>
  <p>Aplikasi menerima token dari luar (URL) dan tetap memakainya setelah login, tanpa meregenerasi token baru.</p>
  <a class="btn" href="lab3_session_fixation.php">Open Lab</a>
</div>

<div class="lab-card">
  <h3>Lab 4 &mdash; Session token di URL</h3>
  <p>Token otentikasi dikirim lewat parameter URL, tercatat di log akses server dan bisa dibajak siapa pun yang membaca log tersebut.</p>
  <a class="btn" href="lab4_token_in_url.php">Open Lab</a>
</div>

<?php include 'footer.php'; ?>
