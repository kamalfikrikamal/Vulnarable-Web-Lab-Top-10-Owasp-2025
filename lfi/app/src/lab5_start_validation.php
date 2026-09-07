<?php
$title = 'Lab 5: Validasi Hanya di Awal Path';
$page = $_GET['page'] ?? 'pages/home.php';
$base = 'pages/';

// NAIVE VALIDATION: hanya memastikan string INPUT dimulai dengan "pages/". Tidak
// menormalisasi (mis. lewat realpath()) untuk memastikan hasil akhirnya benar-benar tetap di
// dalam folder pages/. Input boleh "dimulai" dengan "pages/" tapi tetap mengandung traversal
// setelahnya yang membawa keluar dari folder itu.
$valid = (strpos($page, $base) === 0);
$safe_page = $valid ? $page : 'pages/home.php';

include 'header.php';
?>

<details class="hint-box">
<summary>Lihat Hint</summary>
<p class="hint">Goal: validasi hanya mengecek awal string, bukan hasil akhir path setelah
traversal di-resolve. Selama input dimulai dengan <code>pages/</code>, validasi lolos &mdash;
apa pun yang menyusul setelahnya tetap diproses:</p>
<ul class="hint">
  <li><code>?page=pages/../../../../etc/passwd</code></li>
</ul>
</details>

<?php if (!$valid): ?>
<div class="error-box">Blocked: path harus dimulai dengan "pages/".</div>
<?php endif; ?>

<form method="get">
  <label>Page</label><br>
  <input type="text" name="page" value="<?php echo htmlspecialchars($page); ?>">
  <button type="submit">Load</button>
</form>

<div class="result-box">
<?php include $safe_page; ?>
</div>

<?php include 'footer.php'; ?>
